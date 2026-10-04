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

    protected User $user;

    protected Business $business;

    protected BusinessBranch $branch;

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
            'file' => UploadedFile::fake()->create('iphone.jpg', 500, 'image/jpeg'),
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
            'file' => UploadedFile::fake()->create('manual.pdf', 500, 'application/pdf'),
        ]);

        $response->assertStatus(201);
        $this->assertSame('document', $response->json('attachment.kind'));
    }

    public function test_lists_product_attachments(): void
    {
        $product = $this->makeProduct();
        $this->postJson("/api/products/{$product->id}/attachments", [
            'file' => UploadedFile::fake()->create('front.jpg', 500, 'image/jpeg'),
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
            'file' => UploadedFile::fake()->create('front.jpg', 500, 'image/jpeg'),
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
            'file' => UploadedFile::fake()->create('sneaky.jpg', 500, 'image/jpeg'),
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
            'file' => UploadedFile::fake()->create('kra.pdf', 300, 'application/pdf'),
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
            'file' => UploadedFile::fake()->create('cover.jpg', 500, 'image/jpeg'),
        ]);

        $this->getJson("/api/products/{$product->id}")
            ->assertStatus(200)
            ->assertJsonStructure(['product' => ['cover_url', 'attachments']]);
    }
}
