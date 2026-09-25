<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Services\Notifications\TemplateProvisioner;
use Illuminate\Database\Seeder;

class WhatsAppTemplateSeeder extends Seeder
{
    /**
     * Give every business the template rows its catalogue notifications need.
     *
     * Safe to re-run: wording is only written on create, and template_status is never
     * written here because it mirrors Meta's approval, not ours.
     *
     * @param  int|null  $businessId  Limit to one business, e.g.
     *                                `--class=Database\\Seeders\\WhatsAppTemplateSeeder 7`
     */
    public function run(?int $businessId = null): void
    {
        $provisioner = app(TemplateProvisioner::class);

        $missing = $provisioner->typesMissingWording();

        if ($missing !== []) {
            // Not fatal — a missing wording simply means no template is created — but
            // it must be visible, because the notification would otherwise render as
            // an empty message.
            $this->command?->warn(
                'No wording defined for: '.implode(', ', $missing)
            );
        }

        $businesses = $businessId !== null
            ? Business::where('id', $businessId)->get()
            : Business::all();

        foreach ($businesses as $business) {
            $result = $provisioner->ensureForBusiness($business->id);
            $pruned = $provisioner->pruneOrphans($business->id);

            if ($result['created'] > 0 || $result['updated'] > 0 || $pruned > 0) {
                $this->command?->info(sprintf(
                    'business %d: %d template(s) created, %d backfilled, %d orphan(s) pruned',
                    $business->id,
                    $result['created'],
                    $result['updated'],
                    $pruned
                ));
            }
        }
    }
}
