<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use App\Support\ReceiptQrCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Downloading a receipt PDF was broken for every receipt, from the moment the QR
 * code was added to the design: the controller called BaconQrCode's Encoder as if
 * it were a v2 instance method, `(new Encoder($renderer))->encode($url)`, while the
 * installed library was v3, where `Encoder::encode()` is a static method requiring
 * an error-correction level. The resulting ArgumentCountError is an Error, not an
 * Exception, so the `catch (\Exception)` around it let it escape as a 500 — the
 * PDF never downloaded.
 *
 * The tests below pin the user-facing contract (the PDF downloads) and the QR
 * helper that actually exercises the installed library, so a future API bump fails
 * a test instead of a download.
 */
class ReceiptPdfTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;

    protected BusinessBranch $branch;

    protected User $user;

    protected Receipt $receipt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->branch = BusinessBranch::factory()->create([
            'business_id' => $this->business->id,
        ]);
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

        // business_id is not on Sale's $fillable, so it is assigned directly: a
        // mass-assigned value would be silently dropped and the insert would fail.
        $sale = new Sale([
            'business_branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'total_amount' => 5000,
        ]);
        $sale->business_id = $this->business->id;
        $sale->save();

        $this->receipt = Receipt::create([
            'receipt_number' => 'RCP-TEST-0001',
            'user_id' => $this->user->id,
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'sale_id' => $sale->id,
            'subtotal' => 5000,
            'total' => 5000,
            'amount_paid' => 5000,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        ReceiptItem::create([
            'receipt_id' => $this->receipt->id,
            'product_id' => Product::factory()->create(['business_branch_id' => $this->branch->id])->id,
            'product_name' => 'Test Widget',
            'quantity' => 1,
            'unit_price' => 5000,
            'discount' => 0,
            'line_total' => 5000,
        ]);
    }

    public function test_pdf_is_base64_for_json_accept(): void
    {
        $response = $this->getJson("/api/receipts/{$this->receipt->id}/pdf");

        $response->assertOk()
            ->assertJsonPath('filename', 'receipt-RCP-TEST-0001.pdf');

        $pdf = base64_decode($response->json('pdf'));
        $this->assertStringStartsWith('%PDF', $pdf, 'the JSON payload did not carry a PDF');
    }

    public function test_pdf_downloads_for_direct_request(): void
    {
        $this->get("/api/receipts/{$this->receipt->id}/pdf")
            ->assertStatus(200)
            ->assertHeader('content-disposition');
    }

    public function test_qr_helper_renders_an_svg_data_uri(): void
    {
        $uri = ReceiptQrCode::svgDataUri('https://duukaflow.com');

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $uri);

        $svg = base64_decode(substr($uri, strlen('data:image/svg+xml;base64,')));
        $this->assertStringContainsString('<svg', $svg);
    }

    public function test_pdf_still_downloads_when_qr_generation_fails(): void
    {
        // The QR is decorative. An encoder that rejects its input (here an empty
        // platform URL) must not take the whole receipt down: the controller catches
        // Throwable, so the download has to succeed with the QR simply absent.
        config(['app.url' => '']);

        $response = $this->getJson("/api/receipts/{$this->receipt->id}/pdf");

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', base64_decode($response->json('pdf')));
    }
}
