<?php

namespace App\Services\Notifications;

use App\Models\WhatsAppTemplate;
use App\Services\Notifications\NotificationCatalogue as Catalogue;

/**
 * Gives a business the template rows its catalogue notifications need.
 *
 * The mapping is derived from config('notifications.catalogue') rather than restated
 * here, so the catalogue stays the single source of truth: a WhatsApp notification
 * cannot end up with no template, and a template cannot exist for a notification that
 * was never declared.
 *
 * Re-running is safe and is the normal case, not an edge case. It fills in what is
 * missing and leaves alone what is not:
 *
 *   - body / variables are only written when creating. The owner is allowed to edit
 *     the wording, so re-provisioning must not revert their copy.
 *   - provider_name / language_code are only filled when null. They must match the
 *     template Meta actually approved; overwriting one with our guess is how a send
 *     starts failing on a parameter mismatch.
 *   - template_status is never written here. It mirrors Meta and is only ever set by
 *     the sync command, which is the whole reason a notification is suppressed rather
 *     than sent against an unapproved template.
 */
class TemplateProvisioner
{
    public function __construct(
        private readonly Catalogue $catalogue,
    ) {}

    /**
     * The default wording for each notification type, in {{token}} form.
     *
     * variables is ordered, and that order is the positional contract with Meta, so it
     * must not be re-sorted or inferred from the body.
     *
     * Copy follows the channel rule: short, actionable, one state change. The monthly
     * report is deliberately a nudge with a link and no figures — the numbers go in the
     * email, and a summary in full over WhatsApp is both expensive and unreadable.
     *
     * @var array<string, array{body: string, variables: array<int, string>}>
     */
    private const WORDING = [
        'registration.welcome' => [
            'body' => 'Welcome to DuukaFlow, {{business_name}}. Your business is live and your free trial ends {{trial_ends_at}}.',
            'variables' => ['business_name', 'trial_ends_at'],
        ],
        'subscription.activated' => [
            'body' => 'Subscription active for {{business_name}}. Plan: {{plan_name}}. Valid until {{ends_at}}.',
            'variables' => ['business_name', 'plan_name', 'ends_at'],
        ],
        'subscription.plan_changed' => [
            'body' => 'Your plan changed to {{plan_name}}, effective {{effective_at}}. New limits apply immediately.',
            'variables' => ['plan_name', 'effective_at'],
        ],
        'subscription.renewed' => [
            'body' => 'Renewed. {{plan_name}} runs until {{ends_at}}. Thank you.',
            'variables' => ['plan_name', 'ends_at'],
        ],
        'payment.failed' => [
            'body' => 'Payment of {{amount}} failed. Reason: {{reason}}. Retry here: {{retry_url}}',
            'variables' => ['amount', 'reason', 'retry_url'],
        ],
        'subscription.expired' => [
            'body' => 'Your subscription expired on {{ends_at}}. Reactivate to keep selling: {{renew_url}}',
            'variables' => ['ends_at', 'renew_url'],
        ],
        'subscription.expiring' => [
            'body' => 'Your subscription expires {{ends_at}}, {{days_remaining}} days left. Renew: {{renew_url}}',
            'variables' => ['ends_at', 'days_remaining', 'renew_url'],
        ],
        'trial.ended' => [
            'body' => 'Your free trial ended {{trial_ends_at}}. Subscribe to continue: {{subscribe_url}}',
            'variables' => ['trial_ends_at', 'subscribe_url'],
        ],
        'report.monthly' => [
            'body' => 'Your {{period}} report is ready. View it in DuukaFlow: {{report_url}}',
            'variables' => ['period', 'report_url'],
        ],
        'order.purchase' => [
            'body' => 'New purchase order {{po_number}} from {{supplier_name}}: {{item_count}} items, {{total}}. Approve: {{po_url}}',
            'variables' => ['po_number', 'supplier_name', 'item_count', 'total', 'po_url'],
        ],
        'order.sale' => [
            'body' => 'New sale order {{so_number}} for {{customer_name}}: {{item_count}} items, {{total}}. Fulfil: {{so_url}}',
            'variables' => ['so_number', 'customer_name', 'item_count', 'total', 'so_url'],
        ],
        'inventory.low_stock' => [
            'body' => 'Low stock: {{product_summary}} Reorder level: {{reorder_level}}.',
            'variables' => ['product_summary', 'reorder_level'],
        ],
        'inventory.out_of_stock' => [
            'body' => 'Out of stock: {{product_summary}} Restock to resume sales.',
            'variables' => ['product_summary'],
        ],
    ];

    /**
     * @return array{created: int, updated: int}
     */
    public function ensureForBusiness(int $businessId): array
    {
        $created = 0;
        $updated = 0;

        foreach ($this->catalogue->all() as $type => $entry) {
            // Email-only notifications need no Meta template at all.
            if (! in_array('whatsapp', $entry['channels'], true)) {
                continue;
            }

            $wording = self::WORDING[$type] ?? null;

            if ($wording === null) {
                // A WhatsApp notification with no wording would render to an empty
                // message. Better to leave the gap visible than to send nothing.
                continue;
            }

            $existing = WhatsAppTemplate::withoutGlobalScopes()
                ->where('business_id', $businessId)
                ->where('name', $type)
                ->where('locale', 'en')
                ->first();

            if ($existing === null) {
                WhatsAppTemplate::withoutGlobalScopes()->create([
                    'business_id' => $businessId,
                    'name' => $type,
                    'category' => $entry['category'],
                    'locale' => 'en',
                    'body' => $wording['body'],
                    'variables' => $wording['variables'],
                    'status' => 'pending',
                    'provider_name' => $entry['meta'],
                    'language_code' => 'en',
                    'template_category' => 'UTILITY',
                    'template_status' => 'PENDING',
                    'parameter_format' => 'NAMED',
                    'is_mandatory' => $entry['mandatory'],
                ]);

                $created++;

                continue;
            }

            // Backfill only what is null. See the class docblock.
            $fill = [];

            if (blank($existing->provider_name)) {
                $fill['provider_name'] = $entry['meta'];
            }

            if (blank($existing->language_code)) {
                $fill['language_code'] = 'en';
            }

            if ($fill !== []) {
                $existing->forceFill($fill)->save();
                $updated++;
            }
        }

        return ['created' => $created, 'updated' => $updated];
    }

    /**
     * Delete template rows that match no catalogue type.
     *
     * Only rows with a null provider_name are eligible. A row that has one is a real
     * Meta template and might be referenced by history in notification_deliveries, so
     * it is never removed here even if the catalogue moves on.
     *
     * The rows this actually reclaims are the old hand-written seeds —
     * low_stock_alert, payment_reminder — which used the pre-catalogue naming and
     * could never be dispatched. Leaving them would show the owner templates that
     * silently do nothing.
     */
    public function pruneOrphans(int $businessId): int
    {
        $known = array_keys($this->catalogue->all());

        return WhatsAppTemplate::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->whereNotIn('name', $known)
            ->whereNull('provider_name')
            ->delete();
    }

    /**
     * Catalogue types that send WhatsApp but have no wording, i.e. would render empty.
     * Surfaced so the gap is a visible failure rather than a blank message in the wild.
     *
     * @return array<int, string>
     */
    public function typesMissingWording(): array
    {
        $missing = [];

        foreach ($this->catalogue->all() as $type => $entry) {
            if (in_array('whatsapp', $entry['channels'], true) && ! isset(self::WORDING[$type])) {
                $missing[] = $type;
            }
        }

        return $missing;
    }
}
