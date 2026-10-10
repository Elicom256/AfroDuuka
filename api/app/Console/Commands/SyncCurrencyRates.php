<?php

namespace App\Console\Commands;

use App\Services\CurrencyRateService;
use Illuminate\Console\Command;

class SyncCurrencyRates extends Command
{
    protected $signature = 'duukaflow:currency:sync-rates
                            {--base= : Force a base currency (defaults to each business\'s country currency)}
                            {--business= : Limit to one business id}';

    protected $description = 'Sync currency rates from the configured live provider';

    public function handle(CurrencyRateService $service): int
    {
        $this->info('Syncing currency rates from live provider...');

        $result = $service->syncAllBusinesses(
            $this->option('base') ?: null,
            $this->option('business') ? (int) $this->option('business') : null,
        );

        if (! $result['success']) {
            $this->error($result['message']);

            return self::FAILURE;
        }

        $this->info($result['message']);

        return self::SUCCESS;
    }
}