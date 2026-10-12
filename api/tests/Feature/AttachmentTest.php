<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttachmentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** SOI marker followed by enough bytes to be a plausible truncated JPEG. */
    private const JPEG_BYTES = "\xff\xd8\xff\xe0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00";

    /** PDF header plus a minimal body — enough for the signature check. */
    private const PDF_BYTES = "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n";

    protected User $user;

    protected Business $business;

    protected BusinessBranch $branch;

    /**
     * A fake upload whose bytes are real for its extension.
     *
     * UploadedFile::fake()->create() writes a file of the requested size filled with
     * zeros, which used to satisfy the mimes rule because that rule only reads the
     * client-reported MIME header. It does not satisfy RealFileContent, and should not:
     * these tests are about the upload path working, so they now hand over a file that
     * genuinely is what it claims to be.
     */
    private function fileWithContent(string $name, string $mime, string $bytes): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $bytes)->mimeType($mime);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->branch = BusinessBranch::factory()->create([
            'business_id' => $this->business->id,
        ]);
        // Named explicitly: RoleFactory defaults to 'Operations', which holds no delete
        // authority, and the detach case here is about attachment ownership — not about
        // who may delete. See OperationsRolePermissionsTest for the role boundary.
        $role = Role::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'Executive',
        ]);
        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
        ]);

        Sanctum::actingAs($this->user);
        Storage::fake('public');
    }

    private function makeProduct(?BusinessBranch $branch = null): Product
    {
        return Product::factory()->create([
            'business_branch_id' => ($branch ?? $this->branch)->id,
            'name' => 'Attachable Widget',
            'status' => 'active',
        ]);
    }

    public function test_uploads_product_image(): void
    {
        $product = $this->makeProduct();

        $response = $this->postJson("/api/products/{$product->id}/attachments", [
            'file' => $this->fileWithContent('iphone.jpg', 'image/jpeg', self::JPEG_BYTES),
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'attachment']);

        $this->assertDatabaseHas('attachments', [
            'attachable_type' => Product::class,
            'attachable_id' => $product->id,
            'kind' => 'image',
        ]);

        $attachment = $response->json('attachment');
        Storage::disk('public')->assertExists($attachment['path']);
        $this->assertNotNull($attachment['url']);
        $this->assertSame($product->id, $attachment['attachable_id']);
        $this->assertSame($this->business->id, $attachment['business_id']);
    }

    public function test_uploads_document_as_document_kind(): void
    {
        $product = $this->makeProduct();

        $response = $this->postJson("/api/products/{$product->id}/attachments", [
            'file' => $this->fileWithContent('manual.pdf', 'application/pdf', self::PDF_BYTES),
        ]);

        $response->assertStatus(201);
        $this->assertSame('document', $response->json('attachment.kind'));
    }

    public function test_lists_product_attachments(): void
    {
        $product = $this->makeProduct();
        $this->postJson("/api/products/{$product->id}/attachments", [
            'file' => $this->fileWithContent('front.jpg', 'image/jpeg', self::JPEG_BYTES),
        ]);

        $response = $this->getJson("/api/products/{$product->id}/attachments")
            ->assertStatus(200)
            ->assertJsonStructure(['message', 'attachments']);

        $this->assertCount(1, $response->json('attachments'));
    }

    public function test_deletes_product_attachment(): void
    {
        $product = $this->makeProduct();
        $upload = $this->postJson("/api/products/{$product->id}/attachments", [
            'file' => $this->fileWithContent('front.jpg', 'image/jpeg', self::JPEG_BYTES),
        ]);
        $attachment = $upload->json('attachment');

        $this->deleteJson("/api/products/{$product->id}/attachments/{$attachment['id']}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('attachments', ['id' => $attachment['id']]);
        Storage::disk('public')->assertMissing($attachment['path']);
    }

    public function test_branch_scoped_user_cannot_attach_to_other_branch_product(): void
    {
        $otherBranch = BusinessBranch::factory()->create(['business_id' => $this->business->id]);
        $product = $this->makeProduct($otherBranch);

        $this->postJson("/api/products/{$product->id}/attachments", [
            'file' => $this->fileWithContent('sneaky.jpg', 'image/jpeg', self::JPEG_BYTES),
        ])->assertStatus(404);
    }

    public function test_upload_requires_file(): void
    {
        $product = $this->makeProduct();

        $this->postJson("/api/products/{$product->id}/attachments", [])
            ->assertStatus(422);
    }

    public function test_upload_rejects_disallowed_mime(): void
    {
        $product = $this->makeProduct();

        $this->postJson("/api/products/{$product->id}/attachments", [
            'file' => UploadedFile::fake()->create('virus.exe', 100, 'application/x-msdownload'),
        ])->assertStatus(422);

        $this->assertDatabaseCount('attachments', 0);
    }

    public function test_customer_can_hold_documents(): void
    {
        $customerUser = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => Role::factory()->create(['business_id' => $this->business->id])->id,
        ]);
        $customer = Customer::factory()->create([
            'user_id' => $customerUser->id,
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
        ]);

        $this->postJson("/api/dashboard/customers/{$customer->id}/attachments", [
            'file' => $this->fileWithContent('kra.pdf', 'application/pdf', self::PDF_BYTES),
        ])->assertStatus(201);

        $this->assertDatabaseHas('attachments', [
            'attachable_type' => Customer::class,
            'attachable_id' => $customer->id,
            'kind' => 'document',
        ]);
    }

    public function test_product_show_includes_cover_url(): void
    {
        $product = $this->makeProduct();
        $this->postJson("/api/products/{$product->id}/attachments", [
            'file' => $this->fileWithContent('cover.jpg', 'image/jpeg', self::JPEG_BYTES),
        ]);

        $this->getJson("/api/products/{$product->id}")
            ->assertStatus(200)
            ->assertJsonStructure(['product' => ['cover_url', 'attachments']]);
    }

    /**
     * The mimes rule reads the MIME type out of the client's own multipart headers, so
     * a script announcing image/jpeg passed it. These are the cases that would have
     * stored a file whose extension says one thing and whose contents are another.
     */
    public function test_a_mislabelled_file_is_refused(): void
    {
        $product = $this->makeProduct();

        // A PHP payload with a .jpg name, honestly declared as text/html by the client —
        // the mimes rule would refuse this on the header alone, so the content rule is
        // not what catches it. Asserting it anyway pins the behaviour.
        $this->postJson("/api/products/{$product->id}/attachments", [
            'file' => UploadedFile::fake()->createWithContent('shell.jpg', '<?php system($_GET["c"]); ?>')
                ->mimeType('text/html'),
        ])->assertStatus(422);

        $this->assertDatabaseCount('attachments', 0);
    }

    public function test_a_binary_blob_named_as_an_image_is_refused(): void
    {
        $product = $this->makeProduct();

        // This is the one the old rule could not catch: the client says image/jpeg, the
        // name says .jpg, and mimes is satisfied. Only reading the bytes notices.
        $this->postJson("/api/products/{$product->id}/attachments", [
            'file' => UploadedFile::fake()->createWithContent('payload.jpg', str_repeat("\x00\x01\x02", 64))
                ->mimeType('image/jpeg'),
        ])->assertStatus(422);

        $this->assertDatabaseCount('attachments', 0);
    }

    public function test_a_pdf_named_as_a_pdf_but_holding_a_zip_is_refused(): void
    {
        $product = $this->makeProduct();

        $this->postJson("/api/products/{$product->id}/attachments", [
            'file' => UploadedFile::fake()->createWithContent('report.pdf', "PK\x03\x04".str_repeat("\0", 64))
                ->mimeType('application/pdf'),
        ])->assertStatus(422);

        $this->assertDatabaseCount('attachments', 0);
    }

    public function test_an_ole2_file_named_as_docx_is_refused(): void
    {
        $product = $this->makeProduct();

        // A legacy .doc is an OLE2 compound document, not the ZIP container a .docx is.
        // Swapping one extension for the other is how a macro-enabled file gets past a
        // "documents only" rule.
        $this->postJson("/api/products/{$product->id}/attachments", [
            'file' => UploadedFile::fake()->createWithContent('invoice.docx', "\xd0\xcf\x11\xe0\xa1\xb1\x1a\xe1".str_repeat("\0", 64))
                ->mimeType('application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ])->assertStatus(422);

        $this->assertDatabaseCount('attachments', 0);
    }

    public function test_a_plain_csv_is_accepted(): void
    {
        $product = $this->makeProduct();

        // The rule has to accept the legitimate formats too — a .csv with no binary
        // bytes in it is exactly what should arrive.
        $this->postJson("/api/products/{$product->id}/attachments", [
            'file' => $this->fileWithContent('stock.csv', 'text/csv', "sku,qty\nABC-1,4\nABC-2,9\n"),
        ])->assertStatus(201);

        $this->assertDatabaseCount('attachments', 1);
    }

    public function test_a_binary_blob_named_as_csv_is_refused(): void
    {
        $product = $this->makeProduct();

        $this->postJson("/api/products/{$product->id}/attachments", [
            'file' => UploadedFile::fake()->createWithContent('stock.csv', str_repeat("\x00\x01", 64))
                ->mimeType('text/csv'),
        ])->assertStatus(422);

        $this->assertDatabaseCount('attachments', 0);
    }
}
