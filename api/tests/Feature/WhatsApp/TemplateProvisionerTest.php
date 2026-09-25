<?php

namespace Tests\Feature\WhatsApp;

use App\Models\Business;
use App\Models\WhatsAppTemplate;
use App\Services\Notifications\TemplateProvisioner;
use App\Services\Notifications\TemplateRenderer;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateProvisionerTest extends TestCase
{
    use RefreshDatabase;

    private TemplateProvisioner $provisioner;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provisioner = app(TemplateProvisioner::class);
        $this->business = Business::factory()->create();
    }

    private function whatsappTypes(): array
    {
        $catalogue = config('notifications.catalogue');

        return array_keys(array_filter(
            $catalogue,
            fn (array $entry): bool => in_array('whatsapp', $entry['channels'], true)
        ));
    }

    public function test_it_creates_one_row_per_whatsapp_notification(): void
    {
        $result = $this->provisioner->ensureForBusiness($this->business->id);

        $expected = count($this->whatsappTypes());

        $this->assertSame($expected, $result['created']);
        $this->assertSame(0, $result['updated']);
        $this->assertSame($expected, WhatsAppTemplate::withoutGlobalScopes()->count());
    }

    public function test_every_row_is_named_after_its_notification_type(): void
    {
        $this->provisioner->ensureForBusiness($this->business->id);

        $names = WhatsAppTemplate::withoutGlobalScopes()->pluck('name')->sort()->values()->all();

        $types = $this->whatsappTypes();
        sort($types);

        $this->assertSame($types, $names, 'Template name must equal the notification type it serves.');
    }

    public function test_no_template_is_created_for_an_email_only_notification(): void
    {
        $this->provisioner->ensureForBusiness($this->business->id);

        $this->assertSame(0, WhatsAppTemplate::withoutGlobalScopes()
            ->where('name', 'quotation.sent')->count());
    }

    public function test_each_row_carries_the_catalogue_meta_name_and_mandatory_flag(): void
    {
        $this->provisioner->ensureForBusiness($this->business->id);

        foreach (config('notifications.catalogue') as $type => $entry) {
            if (! in_array('whatsapp', $entry['channels'], true)) {
                continue;
            }

            $template = WhatsAppTemplate::withoutGlobalScopes()->where('name', $type)->first();

            $this->assertNotNull($template, $type);
            $this->assertSame($entry['meta'], $template->provider_name, $type);
            $this->assertSame('en', $template->language_code, $type);
            $this->assertSame('UTILITY', $template->template_category, $type);
            $this->assertSame('NAMED', $template->parameter_format, $type);
            $this->assertSame($entry['mandatory'], $template->is_mandatory, $type);
        }
    }

    public function test_rows_start_pending_because_nothing_is_approved_at_meta_yet(): void
    {
        $this->provisioner->ensureForBusiness($this->business->id);

        $templates = WhatsAppTemplate::withoutGlobalScopes()->get();

        $this->assertCount(
            0,
            $templates->filter(fn (WhatsAppTemplate $t): bool => $t->isApproved())->all(),
            'Seeding must not fabricate a Meta approval.'
        );

        $this->assertSame(
            ['PENDING'],
            $templates->pluck('template_status')->unique()->values()->all()
        );
    }

    public function test_declared_variables_match_the_placeholders_in_the_body(): void
    {
        $this->provisioner->ensureForBusiness($this->business->id);

        $renderer = new TemplateRenderer;

        foreach (WhatsAppTemplate::withoutGlobalScopes()->get() as $template) {
            preg_match_all('/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/', $template->body, $matches);

            $inBody = array_values(array_unique($matches[1]));
            $declared = $template->variables;

            sort($inBody);
            sort($declared);

            $this->assertSame($declared, $inBody, "{$template->name}: body and variables disagree.");
        }
    }

    public function test_every_seeded_template_renders_without_placeholder_errors(): void
    {
        $this->provisioner->ensureForBusiness($this->business->id);

        $renderer = new TemplateRenderer;

        foreach (WhatsAppTemplate::withoutGlobalScopes()->get() as $template) {
            $values = [];

            foreach ($template->variables as $name) {
                $values[$name] = 'sample';
            }

            $rendered = $renderer->render($template->body, $template->variables, $values);

            $this->assertStringNotContainsString('{{', $rendered->body, $template->name);
        }
    }

    public function test_rerunning_does_not_duplicate_rows(): void
    {
        $this->provisioner->ensureForBusiness($this->business->id);
        $count = WhatsAppTemplate::withoutGlobalScopes()->count();

        $second = $this->provisioner->ensureForBusiness($this->business->id);

        $this->assertSame(0, $second['created']);
        $this->assertSame($count, WhatsAppTemplate::withoutGlobalScopes()->count());
    }

    public function test_rerunning_preserves_body_wording_the_owner_edited(): void
    {
        $this->provisioner->ensureForBusiness($this->business->id);

        $template = WhatsAppTemplate::withoutGlobalScopes()->where('name', 'payment.failed')->first();
        $template->forceFill(['body' => 'Our own wording, {{amount}} did not go through.'])->save();

        $this->provisioner->ensureForBusiness($this->business->id);

        $this->assertSame(
            'Our own wording, {{amount}} did not go through.',
            WhatsAppTemplate::withoutGlobalScopes()->where('name', 'payment.failed')->first()->body
        );
    }

    public function test_rerunning_never_resets_a_meta_approval(): void
    {
        $this->provisioner->ensureForBusiness($this->business->id);

        $template = WhatsAppTemplate::withoutGlobalScopes()->where('name', 'payment.failed')->first();
        $template->forceFill(['template_status' => 'APPROVED', 'status' => 'approved'])->save();

        $this->provisioner->ensureForBusiness($this->business->id);

        $fresh = WhatsAppTemplate::withoutGlobalScopes()->where('name', 'payment.failed')->first();

        $this->assertTrue($fresh->isApproved(), 'Re-provisioning wiped a real Meta approval.');
    }

    public function test_it_backfills_a_null_provider_name_but_never_overwrites_one(): void
    {
        $this->provisioner->ensureForBusiness($this->business->id);

        $template = WhatsAppTemplate::withoutGlobalScopes()->where('name', 'payment.failed')->first();
        $template->forceFill(['provider_name' => null])->save();

        $this->provisioner->ensureForBusiness($this->business->id);
        $this->assertSame(
            'payment_failed',
            WhatsAppTemplate::withoutGlobalScopes()->where('name', 'payment.failed')->first()->provider_name
        );

        $template->forceFill(['provider_name' => 'a_name_meta_actually_approved'])->save();
        $this->provisioner->ensureForBusiness($this->business->id);

        $this->assertSame(
            'a_name_meta_actually_approved',
            WhatsAppTemplate::withoutGlobalScopes()->where('name', 'payment.failed')->first()->provider_name
        );
    }

    public function test_it_prunes_the_old_hand_written_seeds(): void
    {
        // The pre-catalogue rows: right shape to look plausible, but no Meta identity
        // and a name no notification resolves to.
        foreach (['low_stock_alert', 'payment_reminder'] as $name) {
            WhatsAppTemplate::withoutGlobalScopes()->create([
                'business_id' => $this->business->id,
                'name' => $name,
                'category' => 'low_stock',
                'body' => 'x',
                'status' => 'approved',
            ]);
        }

        $pruned = $this->provisioner->pruneOrphans($this->business->id);

        $this->assertSame(2, $pruned);
        $this->assertSame(0, WhatsAppTemplate::withoutGlobalScopes()
            ->whereIn('name', ['low_stock_alert', 'payment_reminder'])->count());
    }

    public function test_pruning_never_deletes_a_row_with_a_real_meta_template(): void
    {
        $this->provisioner->ensureForBusiness($this->business->id);

        // A row that no longer maps to the catalogue but is genuinely approved.
        WhatsAppTemplate::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'name' => 'legacy_promo',
            'category' => 'order',
            'body' => 'x',
            'provider_name' => 'legacy_promo_at_meta',
            'template_status' => 'APPROVED',
        ]);

        $pruned = $this->provisioner->pruneOrphans($this->business->id);

        $this->assertSame(0, $pruned);
        $this->assertSame(1, WhatsAppTemplate::withoutGlobalScopes()
            ->where('name', 'legacy_promo')->count());
    }

    public function test_it_only_touches_its_own_business(): void
    {
        $other = Business::factory()->create();

        $this->provisioner->ensureForBusiness($this->business->id);

        $this->assertGreaterThan(0, WhatsAppTemplate::withoutGlobalScopes()
            ->where('business_id', $this->business->id)->count());

        $this->assertSame(0, WhatsAppTemplate::withoutGlobalScopes()
            ->where('business_id', $other->id)->count());
    }

    public function test_no_whatsapp_type_is_missing_wording(): void
    {
        $this->assertSame(
            [],
            $this->provisioner->typesMissingWording(),
            'A WhatsApp notification with no wording would render as an empty message.'
        );
    }

    public function test_the_legacy_service_method_now_provisions_catalogue_rows(): void
    {
        $result = app(WhatsAppService::class)
            ->ensureTemplatesForBusiness($this->business->id);

        $this->assertCount(count($this->whatsappTypes()), $result);
        $this->assertContains('inventory.low_stock', array_column($result, 'name'));
        $this->assertNotContains('low_stock_alert', array_column($result, 'name'));
    }
}
