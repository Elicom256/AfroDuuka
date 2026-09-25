<?php

namespace Tests\Feature\WhatsApp;

use App\Services\Notifications\NotificationCatalogue;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Tests\TestCase;

class NotificationCatalogueTest extends TestCase
{
    public function test_the_shipped_catalogue_is_internally_consistent(): void
    {
        $catalogue = app(NotificationCatalogue::class);

        $types = array_keys($catalogue->all());

        $this->assertContains('registration.welcome', $types);
        $this->assertContains('inventory.low_stock', $types);
        $this->assertContains('inventory.out_of_stock', $types);
        $this->assertCount(14, $types, 'Sale receipt is deferred; the rest should ship.');

        foreach ($types as $type) {
            if (in_array('whatsapp', $catalogue->channelsFor($type), true)) {
                $this->assertNotNull(
                    $catalogue->metaTemplateFor($type),
                    "{$type} sends WhatsApp but has no Meta template."
                );
            }
        }

        $this->assertSame(1, count(array_filter(
            $types,
            fn (string $type): bool => ! in_array('whatsapp', $catalogue->channelsFor($type), true)
        )), 'quotation.sent is the only email-only notification.');
    }

    public function test_quotation_sends_email_only_and_carries_no_meta_template(): void
    {
        $catalogue = app(NotificationCatalogue::class);

        $this->assertSame(['email'], $catalogue->channelsFor('quotation.sent'));
        $this->assertNull($catalogue->metaTemplateFor('quotation.sent'));
    }

    public function test_the_monthly_report_is_email_plus_a_whatsapp_nudge(): void
    {
        $catalogue = app(NotificationCatalogue::class);

        $this->assertSame(['email', 'whatsapp'], $catalogue->channelsFor('report.monthly'));
        $this->assertFalse($catalogue->isMandatory('report.monthly'));
    }

    public function test_inventory_and_order_notifications_are_branch_scoped(): void
    {
        $catalogue = app(NotificationCatalogue::class);

        foreach (['inventory.low_stock', 'inventory.out_of_stock', 'order.purchase', 'order.sale'] as $type) {
            $this->assertTrue($catalogue->isBranchScoped($type), $type);
        }

        $this->assertFalse($catalogue->isBranchScoped('subscription.renewed'));
    }

    public function test_an_unknown_type_lists_the_known_ones(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown notification type "subscription.exploded"');

        app(NotificationCatalogue::class)->get('subscription.exploded');
    }

    public function test_a_whatsapp_entry_without_a_meta_template_is_rejected(): void
    {
        Config::set('notifications.catalogue.broken', [
            'label' => 'Broken',
            'category' => 'system',
            'channels' => ['whatsapp'],
            'mandatory' => true,
            'scope' => 'business',
            'meta' => null,
            'dedupe' => 'broken:{id}',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('sends WhatsApp but declares no meta template');

        app(NotificationCatalogue::class)->get('broken');
    }

    public function test_an_unknown_category_is_rejected(): void
    {
        Config::set('notifications.catalogue.broken', [
            'label' => 'Broken',
            'category' => 'not_a_category',
            'channels' => ['email'],
            'mandatory' => true,
            'scope' => 'business',
            'meta' => null,
            'dedupe' => 'broken:{id}',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not in notifications.categories');

        app(NotificationCatalogue::class)->get('broken');
    }

    public function test_dedupe_keys_are_built_from_the_declared_shape(): void
    {
        $catalogue = app(NotificationCatalogue::class);

        $this->assertSame(
            'registration:welcome:business-7',
            $catalogue->dedupeKey('registration.welcome', ['business_id' => 7])
        );

        $this->assertSame(
            'subscription:created:sub-3:payment-11',
            $catalogue->dedupeKey('subscription.activated', [
                'subscription_id' => 3,
                'payment_id' => 11,
            ])
        );

        $this->assertSame(
            'subscription:reminder:sub-3:bucket-7',
            $catalogue->dedupeKey('subscription.expiring', [
                'subscription_id' => 3,
                'bucket' => 7,
            ])
        );
    }

    public function test_the_expiry_reminder_dedupe_key_changes_as_the_bucket_advances(): void
    {
        $catalogue = app(NotificationCatalogue::class);

        $first = $catalogue->dedupeKey('subscription.expiring', ['subscription_id' => 3, 'bucket' => 7]);
        $later = $catalogue->dedupeKey('subscription.expiring', ['subscription_id' => 3, 'bucket' => 8]);

        $this->assertNotSame($first, $later, 'A new reminder bucket must be able to notify again.');
    }

    public function test_a_missing_identity_placeholder_is_rejected_rather_than_left_in_the_key(): void
    {
        $catalogue = app(NotificationCatalogue::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('missing identity: business_id');

        $catalogue->dedupeKey('registration.welcome', []);
    }

    public function test_a_blank_identity_value_is_rejected(): void
    {
        $catalogue = app(NotificationCatalogue::class);

        $this->expectException(InvalidArgumentException::class);

        $catalogue->dedupeKey('registration.welcome', ['business_id' => '']);
    }
}
