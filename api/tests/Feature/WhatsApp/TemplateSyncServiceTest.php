<?php

namespace Tests\Feature\WhatsApp;

use App\Exceptions\UnapprovedTemplateException;
use App\Models\Business;
use App\Models\WhatsAppConfig;
use App\Models\WhatsAppTemplate;
use App\Services\Notifications\TemplateProvisioner;
use App\Services\Notifications\TemplateResolver;
use App\Services\WhatsApp\TemplateSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TemplateSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();

        app(TemplateProvisioner::class)->ensureForBusiness($this->business->id);
    }

    private function metaConfig(array $overrides = []): WhatsAppConfig
    {
        return WhatsAppConfig::withoutGlobalScopes()->create(array_merge([
            'business_id' => $this->business->id,
            'provider' => 'meta',
            'business_phone' => '+256700000001',
            'phone_number_id' => '123456',
            'whatsapp_business_account_id' => 'WABA-1',
            'access_token' => 'token-abc',
            'is_active' => true,
        ], $overrides));
    }

    private function fakeMeta(array $templates): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['data' => $templates]),
        ]);
    }

    public function test_it_marks_a_matched_template_approved(): void
    {
        $this->fakeMeta([
            ['name' => 'registration_welcome', 'language' => 'en', 'status' => 'APPROVED', 'category' => 'UTILITY', 'parameter_format' => 'NAMED'],
        ]);

        $result = app(TemplateSyncService::class)->sync($this->metaConfig());

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $result['approved']);

        $template = WhatsAppTemplate::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->where('name', 'registration.welcome')
            ->firstOrFail();

        $this->assertSame('APPROVED', $template->template_status);
        $this->assertNotNull($template->last_synced_at);
    }

    public function test_an_approved_template_then_really_dispatches(): void
    {
        $this->fakeMeta([
            ['name' => 'registration_welcome', 'language' => 'en', 'status' => 'APPROVED'],
        ]);

        $config = $this->metaConfig();
        app(TemplateSyncService::class)->sync($config);

        // This is the whole point of the command: approval is the gate on sending, so
        // before the sync this throws and after it the message renders.
        $rendered = app(TemplateResolver::class)->forWhatsApp(
            $this->business->id,
            'registration.welcome',
            ['business_name' => 'Acme', 'trial_ends_at' => '30 Sep 2026'],
            $config
        );

        $this->assertSame(
            'Welcome to DuukaFlow, Acme. Your business is live and your free trial ends 30 Sep 2026.',
            $rendered->body
        );
    }

    public function test_an_unapproved_template_still_blocks_dispatch(): void
    {
        $this->fakeMeta([
            ['name' => 'registration_welcome', 'language' => 'en', 'status' => 'PENDING'],
        ]);

        $config = $this->metaConfig();
        app(TemplateSyncService::class)->sync($config);

        $this->expectException(UnapprovedTemplateException::class);

        app(TemplateResolver::class)->forWhatsApp(
            $this->business->id,
            'registration.welcome',
            ['business_name' => 'Acme', 'trial_ends_at' => '30 Sep 2026'],
            $config
        );
    }

    public function test_it_never_touches_the_owner_edited_copy(): void
    {
        $this->fakeMeta([
            ['name' => 'registration_welcome', 'language' => 'en', 'status' => 'APPROVED'],
        ]);

        $template = WhatsAppTemplate::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->where('name', 'registration.welcome')
            ->firstOrFail();

        $template->forceFill(['body' => 'My own wording {{business_name}}'])->save();

        app(TemplateSyncService::class)->sync($this->metaConfig());

        $this->assertSame(
            'My own wording {{business_name}}',
            $template->fresh()->body,
            'The sync overwrote copy the owner had edited.'
        );
    }

    public function test_a_rejected_status_is_recorded(): void
    {
        $this->fakeMeta([
            ['name' => 'registration_welcome', 'language' => 'en', 'status' => 'REJECTED'],
        ]);

        $result = app(TemplateSyncService::class)->sync($this->metaConfig());

        $this->assertSame(1, $result['rejected']);

        $this->assertSame(
            'REJECTED',
            WhatsAppTemplate::withoutGlobalScopes()
                ->where('business_id', $this->business->id)
                ->where('name', 'registration.welcome')
                ->firstOrFail()
                ->template_status
        );
    }

    public function test_a_limited_template_is_treated_as_rejected(): void
    {
        // Meta sends LIMITED when quality drops below a threshold. It will not accept
        // the template, so treating it as still-usable would fail every send.
        $this->fakeMeta([
            ['name' => 'registration_welcome', 'language' => 'en', 'status' => 'LIMITED'],
        ]);

        $result = app(TemplateSyncService::class)->sync($this->metaConfig());

        $this->assertSame(1, $result['rejected']);
    }

    public function test_an_unrecognised_status_leaves_the_row_alone(): void
    {
        $this->fakeMeta([
            ['name' => 'registration_welcome', 'language' => 'en', 'status' => 'ARCHIVED_BY_META'],
        ]);

        app(TemplateSyncService::class)->sync($this->metaConfig());

        $this->assertSame(
            'PENDING',
            WhatsAppTemplate::withoutGlobalScopes()
                ->where('business_id', $this->business->id)
                ->where('name', 'registration.welcome')
                ->firstOrFail()
                ->template_status,
            'An unknown Meta status was recorded as a decision.'
        );
    }

    public function test_a_failed_read_leaves_approved_templates_approved(): void
    {
        // The important one. An empty response is indistinguishable from "no
        // templates", and revoking approval on that basis silently stops delivery.
        //
        // One stub with a counter rather than two Http::fake() calls: a second fake()
        // appends another stub and the first one still wins, so the failure would never
        // actually be served.
        $calls = 0;

        Http::fake(function () use (&$calls) {
            $calls++;

            return $calls === 1
                ? Http::response(['data' => [['name' => 'registration_welcome', 'language' => 'en', 'status' => 'APPROVED']]])
                : Http::response(['error' => ['message' => 'Service unavailable']], 503);
        });

        $config = $this->metaConfig();

        $first = app(TemplateSyncService::class)->sync($config);
        $this->assertTrue($first['ok']);

        $result = app(TemplateSyncService::class)->sync($config);

        $this->assertFalse($result['ok'], 'A 503 was reported as a successful sync.');
        $this->assertSame(0, $result['fetched']);
        $this->assertSame(
            'APPROVED',
            WhatsAppTemplate::withoutGlobalScopes()
                ->where('business_id', $this->business->id)
                ->where('name', 'registration.welcome')
                ->firstOrFail()
                ->template_status,
            'A failed read revoked an approved template.'
        );
    }

    public function test_a_malformed_body_does_not_throw(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['data' => 'not-an-array']),
        ]);

        $result = app(TemplateSyncService::class)->sync($this->metaConfig());

        $this->assertFalse($result['ok']);
        $this->assertSame(0, $result['fetched']);
    }

    public function test_it_backfills_provider_name_when_null(): void
    {
        $this->fakeMeta([
            ['name' => 'registration_welcome', 'language' => 'en', 'status' => 'APPROVED'],
        ]);

        WhatsAppTemplate::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->where('name', 'registration.welcome')
            ->update(['provider_name' => null]);

        app(TemplateSyncService::class)->sync($this->metaConfig());

        $this->assertSame(
            'registration_welcome',
            WhatsAppTemplate::withoutGlobalScopes()
                ->where('business_id', $this->business->id)
                ->where('name', 'registration.welcome')
                ->firstOrFail()
                ->provider_name
        );
    }

    public function test_it_does_not_match_a_different_business_template(): void
    {
        $other = Business::factory()->create();
        app(TemplateProvisioner::class)->ensureForBusiness($other->id);

        $this->fakeMeta([
            ['name' => 'registration_welcome', 'language' => 'en', 'status' => 'APPROVED'],
        ]);

        app(TemplateSyncService::class)->sync($this->metaConfig());

        $this->assertSame(
            'PENDING',
            WhatsAppTemplate::withoutGlobalScopes()
                ->where('business_id', $other->id)
                ->where('name', 'registration.welcome')
                ->firstOrFail()
                ->template_status,
            'A sync approved another business\'s template.'
        );
    }

    public function test_the_demo_provider_syncs_nothing(): void
    {
        $config = $this->metaConfig(['provider' => 'demo']);

        $result = app(TemplateSyncService::class)->sync($config);

        $this->assertFalse($result['ok']);
        $this->assertSame(0, $result['fetched']);
    }

    public function test_a_config_without_a_waba_id_makes_no_request(): void
    {
        Http::fake();

        $result = app(TemplateSyncService::class)->sync(
            $this->metaConfig(['whatsapp_business_account_id' => null])
        );

        $this->assertFalse($result['ok']);
        Http::assertNothingSent();
    }

    public function test_the_command_reports_a_clean_sync(): void
    {
        $this->fakeMeta([
            ['name' => 'registration_welcome', 'language' => 'en', 'status' => 'APPROVED'],
        ]);

        $this->metaConfig();

        $this->artisan('duukaflow:whatsapp:sync-templates')
            ->assertSuccessful();
    }

    public function test_the_command_fails_when_a_business_cannot_be_read(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['error' => ['message' => 'Invalid token']], 401),
        ]);

        $this->metaConfig();

        $this->artisan('duukaflow:whatsapp:sync-templates')
            ->assertFailed();
    }

    public function test_the_command_skips_configs_without_a_token(): void
    {
        Http::fake();

        $this->artisan('duukaflow:whatsapp:sync-templates')->assertSuccessful();

        Http::assertNothingSent();
    }
}
