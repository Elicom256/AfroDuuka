<?php

return [

    /*
    |---------------------------------------------------------------------------
    | Preference categories
    |---------------------------------------------------------------------------
    |
    | These are the buckets a recipient can opt in and out of, and the bucket
    | written to notification_deliveries.category. Keep them in sync with the
    | comment on the category column in the notification_deliveries migration.
    |
    */

    'categories' => [
        'subscription',
        'payment',
        'security',
        'inventory',
        'order',
        'report',
        'system',
    ],

    /*
    |---------------------------------------------------------------------------
    | The catalogue
    |---------------------------------------------------------------------------
    |
    | One entry per notification, keyed by its `type`, which is also the
    | template_key. That equality is deliberate and load-bearing: WhatsAppTemplate
    | is resolved on an exact name match, never a category or suffix match, because
    | subscription.created, order.purchase.created and order.sale.created all end in
    | "created" and a suffix match would route one notification into another's
    | template.
    |
    | channels  Which channels carry this. Email and WhatsApp are not
    |           interchangeable: short state-change alerts go to WhatsApp, anything
    |           document-shaped goes to email, and a summary gets an email plus a
    |           one-line WhatsApp nudge rather than the same content twice.
    | mandatory Transactional. Bypasses preferences and cannot be opted out of.
    | scope     business (whole tenant) or branch (one branch only).
    | meta      The Meta-approved template name. Null means the notification is
    |           email-only and needs no WhatsApp approval.
    | dedupe    Shape of the deterministic key, with {placeholders} filled from
    |           $identity. Must contain no timestamp: two events that should each
    |           notify have to differ by something other than when they fired.
    |
    */

    'catalogue' => [

        'registration.welcome' => [
            'label' => 'Business registered',
            'category' => 'system',
            'channels' => ['email', 'whatsapp'],
            'mandatory' => true,
            'scope' => 'business',
            'meta' => 'duukaflow_welcome',
            'dedupe' => 'registration:welcome:business-{business_id}',
        ],

        'subscription.activated' => [
            'label' => 'Subscription activated',
            'category' => 'subscription',
            'channels' => ['email', 'whatsapp'],
            'mandatory' => true,
            'scope' => 'business',
            'meta' => 'subscription_activated',
            'dedupe' => 'subscription:created:sub-{subscription_id}:payment-{payment_id}',
        ],

        'subscription.plan_changed' => [
            'label' => 'Plan changed',
            'category' => 'subscription',
            'channels' => ['email', 'whatsapp'],
            'mandatory' => true,
            'scope' => 'business',
            'meta' => 'plan_changed',
            'dedupe' => 'subscription:plan_changed:sub-{subscription_id}:plan-{plan_id}:{effective_at}',
        ],

        'subscription.renewed' => [
            'label' => 'Subscription renewed',
            'category' => 'subscription',
            'channels' => ['email', 'whatsapp'],
            'mandatory' => true,
            'scope' => 'business',
            'meta' => 'subscription_renewed',
            'dedupe' => 'subscription:renewed:sub-{subscription_id}:{period_start}',
        ],

        'payment.failed' => [
            'label' => 'Payment failed',
            'category' => 'payment',
            'channels' => ['email', 'whatsapp'],
            'mandatory' => true,
            'scope' => 'business',
            'meta' => 'payment_failed',
            'dedupe' => 'payment:failed:payment-{payment_id}',
        ],

        'subscription.expired' => [
            'label' => 'Subscription expired',
            'category' => 'subscription',
            'channels' => ['email', 'whatsapp'],
            'mandatory' => true,
            'scope' => 'business',
            'meta' => 'subscription_expired',
            'dedupe' => 'subscription:expired:sub-{subscription_id}:{ends_at}',
        ],

        'subscription.expiring' => [
            'label' => 'Subscription expiring soon',
            'category' => 'subscription',
            'channels' => ['whatsapp'],
            'mandatory' => true,
            'scope' => 'business',
            'meta' => 'subscription_expiring',
            'dedupe' => 'subscription:reminder:sub-{subscription_id}:bucket-{bucket}',
        ],

        'trial.ended' => [
            'label' => 'Free trial ended',
            'category' => 'subscription',
            'channels' => ['email', 'whatsapp'],
            'mandatory' => false,
            'scope' => 'business',
            'meta' => 'trial_ended',
            'dedupe' => 'trial:ended:sub-{subscription_id}:{trial_ends_at}',
        ],

        'report.monthly' => [
            'label' => 'Monthly performance report',
            'category' => 'report',
            'channels' => ['email', 'whatsapp'],
            'mandatory' => false,
            'scope' => 'business',
            'meta' => 'monthly_report_ready',
            'dedupe' => 'report:monthly:business-{business_id}:{period}',
        ],

        'quotation.sent' => [
            'label' => 'Quotation sent to customer',
            'category' => 'order',
            'channels' => ['email'],
            'mandatory' => true,
            'scope' => 'business',
            'meta' => null,
            'dedupe' => 'quotation:sent:quotation-{quotation_id}:v{version}',
        ],

        'order.purchase' => [
            'label' => 'New purchase order',
            'category' => 'order',
            'channels' => ['whatsapp'],
            'mandatory' => true,
            'scope' => 'branch',
            'meta' => 'purchase_order_created',
            'dedupe' => 'order:purchase:business-{business_id}:branch-{business_branch_id}:po-{purchase_order_id}',
        ],

        'order.sale' => [
            'label' => 'New sale order',
            'category' => 'order',
            'channels' => ['whatsapp'],
            'mandatory' => true,
            'scope' => 'branch',
            'meta' => 'sale_order_created',
            'dedupe' => 'order:sale:business-{business_id}:branch-{business_branch_id}:so-{sale_order_id}',
        ],

        'inventory.low_stock' => [
            'label' => 'Low stock',
            'category' => 'inventory',
            'channels' => ['whatsapp'],
            'mandatory' => false,
            'scope' => 'branch',
            'meta' => 'low_stock_alert',
            'dedupe' => 'inventory:low_stock:business-{business_id}:branch-{business_branch_id}:product-{product_id}:episode-{alert_episode}',
        ],

        'inventory.out_of_stock' => [
            'label' => 'Out of stock',
            'category' => 'inventory',
            'channels' => ['whatsapp'],
            'mandatory' => false,
            'scope' => 'branch',
            'meta' => 'out_of_stock_alert',
            'dedupe' => 'inventory:out_of_stock:business-{business_id}:branch-{business_branch_id}:product-{product_id}:episode-{alert_episode}',
        ],

    ],

    /*
    |---------------------------------------------------------------------------
    | Inventory alert behaviour
    |---------------------------------------------------------------------------
    |
    | state-based, not threshold-based: 10 -> 4 against a reorder level of 5 fires
    | once, and a re-check at 4 must stay silent until the item is replenished.
    |
    */

    'inventory' => [
        'cooldown_hours' => (int) env('WHATSAPP_STOCK_ALERT_COOLDOWN_HOURS', 24),
        'top_n' => (int) env('WHATSAPP_STOCK_ALERT_TOP_N', 5),
    ],

    /*
    |---------------------------------------------------------------------------
    | Email preferences
    |---------------------------------------------------------------------------
    */

    'email' => [
        'preference_checked' => (bool) env('EMAIL_PREFERENCE_CHECKED', true),
        'transactional_categories' => ['subscription', 'payment', 'security'],
    ],

    /*
    |---------------------------------------------------------------------------
    | Retry budget
    |---------------------------------------------------------------------------
    |
    | Backoff is deliberately not an exponential default. A notification is worth
    | retrying within a few minutes (someone is waiting on a payment failure) and not
    | worth retrying half an hour later, by which point they have seen the app.
    |
    */

    'retry' => [
        'max_attempts' => (int) env('WHATSAPP_MAX_ATTEMPTS', 3),
        'backoff' => array_map('intval', explode(',', (string) env('WHATSAPP_RETRY_BACKOFF', '30,300,1800'))),
    ],

];
