<?php

namespace App\Support\Features;

use App\Models\Subscription;
use App\Support\Tenant\BusinessContext;

/**
 * Resolves whether an optional feature is switched on for a business.
 *
 * There is deliberately no new table and no new column here. A business already
 * expressed these toggles in the per-feature settings rows it seeds during
 * onboarding (ReportsSettings, PromotionsSettings, AttendanceSettings,
 * CustomersSettings, SuppliersSettings) and its subscription already names the
 * features its plan includes (`plans.features`). This service only reads those
 * two sources and folds them into one booleans, so the api and the sidebars
 * cannot disagree about what is "on" — both ask the same question.
 *
 * Order, most specific first:
 *   1. an explicit per-business settings row wins outright;
 *   2. otherwise a populated plan `features` list decides (a plan that names
 *      its features is authoritative for them);
 *   3. otherwise the shipped default in config/features.php.
 *
 * Resolution is only ever consulted for a feature that exists in the catalogue;
 * an unknown key is off rather than silently permissive.
 */
class FeatureFlags
{
    public function enabled(string $feature, ?int $businessId = null): bool
    {
        $definition = config("features.{$feature}");

        if (! is_array($definition)) {
            return false;
        }

        $businessId ??= app(BusinessContext::class)->businessId();

        if ($businessId !== null) {
            $explicit = $this->explicitSetting($feature, $businessId);
            if ($explicit !== null) {
                return $explicit;
            }

            $plan = $this->planAllows($feature, $businessId);
            if ($plan !== null) {
                return $plan;
            }
        }

        return (bool) ($definition['default'] ?? false);
    }

    public function label(string $feature): string
    {
        $label = config("features.{$feature}.label");

        return is_string($label) && $label !== '' ? $label : ucfirst($feature);
    }

    /**
     * The message a refused request carries. Written for a person, not a log:
     * it names the feature and points at where it can be turned on, so the api
     * answer alone tells the user what happened and what to do about it.
     */
    public function message(string $feature): string
    {
        return sprintf(
            '%s is not enabled for your business. An administrator can turn it on in Settings.',
            $this->label($feature),
        );
    }

    private function explicitSetting(string $feature, int $businessId): ?bool
    {
        $model = config("features.{$feature}.model");

        if (! is_string($model) || ! class_exists($model)) {
            return null;
        }

        $statuses = $model::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->pluck('status');

        if ($statuses->isEmpty()) {
            return null;
        }

        /*
         * A business can legitimately carry both an 'enabled' and a 'disabled'
         * row for one feature: the onboarding seeder has, historically, passed
         * `status` inside the firstOrCreate search attributes, which creates a
         * second row instead of updating the first. Rather than let the newest
         * row win by accident, treat any 'enabled' row as "on" — that is what
         * the toggle in Settings means to a user, and it makes the resolution
         * independent of row order.
         */
        return $statuses->contains('enabled');
    }

    private function planAllows(string $feature, int $businessId): ?bool
    {
        $features = Subscription::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->where('status', 'active')
            ->with('plan')
            ->latest('id')
            ->first()
            ?->plan
            ?->features;

        if (! is_array($features) || $features === []) {
            return null;
        }

        return in_array($feature, $features, true);
    }
}
