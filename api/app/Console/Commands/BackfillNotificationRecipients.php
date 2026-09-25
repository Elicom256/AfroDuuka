<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Models\NotificationRecipient;
use App\Services\Notifications\RecipientProvisioner;
use Illuminate\Console\Command;

/**
 * Seeds recipients for businesses that predate this feature.
 *
 * Provisioning at business creation only helps businesses created after it shipped.
 * Every existing business would otherwise have no recipients, and the dispatcher's last
 * resort is to log no_recipient rather than guess — so they would go quiet without any
 * visible error.
 */
class BackfillNotificationRecipients extends Command
{
    protected $signature = 'duukaflow:notifications:backfill-recipients
                            {--business= : Limit to one business id}
                            {--dry-run : Report what would be created without writing}';

    protected $description = 'Seed owner notification recipients for existing businesses';

    public function handle(RecipientProvisioner $provisioner): int
    {
        $query = Business::query()->orderBy('id');

        if ($businessId = $this->option('business')) {
            $query->whereKey((int) $businessId);
        }

        $created = 0;
        $skipped = 0;
        $alreadyHad = 0;

        $total = $query->count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunkById(50, function ($businesses) use ($provisioner, &$created, &$skipped, &$alreadyHad, $bar) {
            foreach ($businesses as $business) {
                $hasOwner = NotificationRecipient::withoutGlobalScopes()
                    ->where('business_id', $business->id)
                    ->where('label', NotificationRecipient::LABEL_OWNER)
                    ->exists();

                if ($hasOwner) {
                    $alreadyHad++;
                }

                if (! $this->option('dry-run')) {
                    $result = $provisioner->ensureOwner($business);

                    $created += $result['created'];
                    $skipped += count($result['skipped']);
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        if ($this->option('dry-run')) {
            $this->info("Dry run. {$total} businesses inspected, nothing written.");
            $this->line("  {$alreadyHad} already have an owner recipient.");

            return self::SUCCESS;
        }

        $this->info("Backfilled {$created} recipient rows across {$total} businesses.");
        $this->line("  {$alreadyHad} already had an owner recipient.");
        $this->line("  {$skipped} channels skipped because the address did not normalise.");

        if ($skipped > 0) {
            // Worth surfacing rather than only logging: a skipped WhatsApp channel
            // means that business gets no WhatsApp stock or order alerts, and the owner
            // has no way to know why.
            $this->warn('  Skipped channels need a valid phone or email on the business or its owner user.');
        }

        return self::SUCCESS;
    }
}
