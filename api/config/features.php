<?php

use App\Models\CoreSettings\AttendanceSettings;
use App\Models\CoreSettings\CustomersSettings;
use App\Models\CoreSettings\PromotionsSettings;
use App\Models\CoreSettings\ReportsSettings;
use App\Models\CoreSettings\SuppliersSettings;

/*
|--------------------------------------------------------------------------
| Feature catalogue
|--------------------------------------------------------------------------
|
| The single source of truth for the optional features a business can switch
| on. Every entry carries:
|
|   - label:  the human name shown when the feature is refused, and in Settings.
|   - default: the value used when neither a per-business override nor the
|              business's plan says anything about the feature. This is what a
|              brand new business gets before anyone touches Settings.
|   - model:  the per-business settings model that stores the explicit toggle,
|              or null when the feature has no settings row (plan/default only).
|
| Resolution order, most specific first, lives in App\Support\Features\FeatureFlags:
| an explicit settings row wins, then a populated plan `features` list, then the
| default here.
|
| The keys are the strings used with the `feature` middleware on routes.
*/

return [
    'reports' => [
        'label' => 'Reports',
        'default' => false,
        'model' => ReportsSettings::class,
    ],

    'promotions' => [
        'label' => 'Promotions',
        'default' => false,
        'model' => PromotionsSettings::class,
    ],

    'attendance' => [
        'label' => 'Attendance',
        'default' => false,
        'model' => AttendanceSettings::class,
    ],

    /*
     * Customers and Suppliers ship enabled: they are core People features every
     * business is expected to use, so switching them off is opt-out rather than
     * opt-in. See App\Services\CoreBusinessSettings.
     */
    'customers' => [
        'label' => 'Customers',
        'default' => true,
        'model' => CustomersSettings::class,
    ],

    'suppliers' => [
        'label' => 'Suppliers',
        'default' => true,
        'model' => SuppliersSettings::class,
    ],
];
