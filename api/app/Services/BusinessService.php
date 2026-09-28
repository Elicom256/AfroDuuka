<?php

namespace App\Services;

use App\Events\WhatsAppNotificationEvents\BusinessRegistered;
use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Country;
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

    /**
     * businesses.country_id is NOT NULL but the registration form does not collect a
     * country, so it has to be resolved here or business creation fails outright.
     * Uganda is the primary market (it is also the +256 default used across the
     * codebase); fall back to the first seeded country if Uganda is absent.
     */
    private function resolveDefaultCountryId(): ?int
    {
        return Country::query()
            ->where('iso_alpha2', 'UG')
            ->orWhere('name', 'Uganda')
            ->orderBy('id')
            ->value('id')
            ?? Country::query()->orderBy('id')->value('id');
    }

    public function create(array $data, User $user): Business
    {
        // Create the business with the user's phone
        $business = Business::create([
            'name' => $data['name'],
            'email' => $data['email'] ?? $user->email,
            'phone' => $user->phone,
            'address' => $data['address'],
            'business_category_id' => $data['business_category_id'],
            'country_id' => $data['country_id'] ?? $this->resolveDefaultCountryId(),
        ]);

        // Dispatch business registration event
        event(new BusinessRegistered($business));

        // Create the Executive role for this business
        $executiveRole = Role::create([
            'name' => 'Executive',
            'business_id' => $business->id,
        ]);

        $existingRoleNames = Role::where('business_id', $business->id)->pluck('name')->all();
        $new_roles = ['Operations', 'Other Staff', 'editor', 'customer', 'supplier'];
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

        $this->provisionRecipients($business, $user);

        return $business;
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
