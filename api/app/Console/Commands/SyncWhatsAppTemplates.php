<?php

namespace App\Console\Commands;

use App\Models\WhatsAppConfig;
use App\Services\WhatsApp\TemplateSyncService;
use Illuminate\Console\Command;

/**
 * Pulls Meta's template approval status into our rows.
 *
 * Until this has run for a business, every one of its templates is PENDING and
 * TemplateResolver suppresses every WhatsApp notification, so this is the step that
 * switches real sending on. It is intentionally a no-op-by-default for the demo
 * provider, which has no remote registry to read.
 */
class SyncWhatsAppTemplates extends Command
{
    protected $signature = 'duukaflow:whatsapp:sync-templates
                            {--business= : Limit to one business id}
                            {--include-inactive : Also sync businesses whose config is inactive}';

    protected $description = 'Sync WhatsApp template approval status from Meta onto local templates';

    public function handle(TemplateSyncService $sync): int
    {
        $query = WhatsAppConfig::withoutGlobalScopes()
            ->whereNotNull('access_token');

        if (! $this->option('include-inactive')) {
            $query->where('is_active', true);
        }

        if ($businessId = $this->option('business')) {
            $query->where('business_id', (int) $businessId);
        }

        $configs = $query->orderBy('business_id')->get();

        if ($configs->isEmpty()) {
            $this->warn('No businesses with a stored access token. Nothing to sync.');

            return self::SUCCESS;
        }

        $rows = [];
        $failed = 0;

        foreach ($configs as $config) {
            try {
                $result = $sync->sync($config);
            } catch (\Throwable $e) {
                // One business's bad token must not stop the rest of the sync.
                $failed++;
                $rows[] = [$config->business_id, $config->provider, 'ERROR', $e->getMessage()];

                continue;
            }

            if (! $result['ok']) {
                $failed++;
            }

            $rows[] = [
                $config->business_id,
                $config->provider,
                $result['fetched'],
                sprintf(
                    'approved=%d pending=%d rejected=%d unchanged=%d unmatched=%d',
                    $result['approved'],
                    $result['pending'],
                    $result['rejected'],
                    $result['unchanged'],
                    $result['unmatched'],
                ),
            ];
        }

        $this->newLine();
        $this->table(['Business', 'Provider', 'Remote', 'Result'], $rows);

        // Non-zero when at least one business could not be read, so a scheduled run or
        // a CI check notices. A partial sync that silently reports success is how a
        // business ends up quietly not receiving messages.
        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
