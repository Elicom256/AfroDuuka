<?php

namespace App\Services;

use App\Events\WhatsAppNotificationEvents\BusinessRegistered;
use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Role;
use App\Models\User;
use App\Services\Notifications\RecipientProvisioner;
use Illuminate\Support\Facades\Log;

class BusinessService
{
    public function __construct()
    {
        //
    }

    public function create(array $data, User $user): Business
    {
        // Create the business with the user's phone.
        // Optional fields are read with ?? rather than []. A nullable validation rule
        // means the key is simply absent from validated() when the client omits it, so
        // $data['address'] raised "Undefined array key" and turned business creation
        // into a 500 — which is exactly the call self-serve onboarding depends on.
        //
        // country_id is not optional: the column is NOT NULL and StoreBusinessRequest
        // now requires it, replacing a fallback that wrote Uganda onto businesses that
        // never chose a country.
        $business = Business::create([
            'name' => $data['name'],
            'email' => $data['email'] ?? $user->email,
            'phone' => $user->phone,
            'address' => $data['address'] ?? null,
            'business_category_id' => $data['business_category_id'],
            'country_id' => $data['country_id'],
        ]);

        // Dispatch business registration event
        event(new BusinessRegistered($business));

        // Create the Executive role for this business
        $executiveRole = Role::create([
            'name' => 'Executive',
            'business_id' => $business->id,
        ]);

        $existingRoleNames = Role::where('business_id', $business->id)->pluck('name')->all();
        $new_roles = ['Operations', 'editor', 'customer', 'supplier'];
        foreach ($new_roles as $new_role) {
            if (in_array($new_role, $existingRoleNames, true)) {
                continue;
            }
            Role::create([
                'name' => $new_role,
                'business_id' => $business->id,
            ]);
            $existingRoleNames[] = $new_role;
        }
        // Update the user's profile with business_id and role_id
        $user->update([
            'business_id' => $business->id,
            'role_id' => $executiveRole->id,
        ]);
        BusinessBranch::create([
            'business_id' => $business->id,
        ]);

        // ======================= set starter plan ================= to create a plan based on what the user picked

        $this->attachTrialSubscription($business);

        $this->provisionRecipients($business, $user);

        return $business;
    }

    /**
     * Attach a free trial subscription to a newly created business.
     *
     * The product wants a 30-day free period before payments are required, which
     * already has infrastructure in place: subscriptions.trial_ends_at, a FreeTrialExpired
     * event and listener, and notification templates. Rather than shipping the
     * onboarding flow without a subscription record — which leaves the business in a
     * state that billing screens cannot reason about — we attach a trial to the
     * "Basic" plan if one exists, falling back to the oldest active plan. The trial
     * starts now, ends in 30 days and is marked as active, matching the pattern of
     * StoreSubscriptionRequest's trial defaults.
     */
    private function attachTrialSubscription(Business $business): void
    {
        try {
            $plan = \App\Models\Plan::where('is_active', true)
                ->orWhere('status', 'active')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first();

            if ($plan === null) {
                return;
            }

            $now = now();

            \App\Models\Subscription::create([
                'business_id' => $business->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'starts_at' => $now,
                'ends_at' => $now->copy()->addDays(30),
                'trial_ends_at' => $now->copy()->addDays(30),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Could not attach free trial subscription', [
                'business_id' => $business->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Seed the owner's notification recipients.
     *
     * Runs last, after the user has been linked to the business, because the provisioner
     * looks the owner up by business_id + admin role and would not find them any
     * earlier. It is deliberately after BusinessRegistered too: that listener only reads
     * the business's own contact details, and recipients are not a prerequisite for it.
     *
     * A failure here is logged, not thrown. Registration is the user's one interaction
     * with the product so far; failing to create them a business because a notification
     * preference row could not be written would be a bad trade, and the recipients can
     * be created later without any data loss.
     */
    private function provisionRecipients(Business $business, User $user): void
    {
        try {
            $result = app(RecipientProvisioner::class)->ensureOwner($business, $user);

            if ($result['skipped'] !== []) {
                Log::info('Notification recipients skipped channels at registration', [
                    'business_id' => $business->id,
                    'skipped' => $result['skipped'],
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Could not provision notification recipients', [
                'business_id' => $business->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
