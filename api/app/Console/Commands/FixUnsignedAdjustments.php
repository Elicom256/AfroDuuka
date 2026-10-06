<?php

namespace App\Console\Commands;

use App\Enums\CashFlowDirection;
use App\Enums\CashFlowType;
use App\Models\CashFlow;
use App\Services\UnsignedAdjustmentResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Finds manual adjustments that never recorded which way the money moved.
 *
 * These rows are inert, not wrong: CashFlow::cashEffect() returns 0 for an adjustment
 * with no direction, so they leave the cash balance untouched. That is the safe
 * direction to fail in, but it is still a hole in the books, and it is invisible from
 * the dashboard because nothing else reports it.
 *
 * The sign cannot be recovered automatically. The adjustment dialog used to offer
 * "Payment In" and "Payment Out", the endpoint then overwrote the submitted type with
 * 'adjustment', and direction was never stored, so the operator's original choice left
 * no trace to infer from. Setting a value here is therefore a human decision, and this
 * command exists to make that decision possible instead of guessing at it in a
 * migration.
 */
class FixUnsignedAdjustments extends Command
{
    protected $signature = 'duukaflow:finance:unsigned-adjustments
                            {--business= : Limit to one business id}
                            {--code= : Set the direction of this one transaction_code and exit}
                            {--direction= : credit or debit, required with --code}';

    protected $description = 'List manual adjustments that have no direction, or set one';

    public function __construct(private UnsignedAdjustmentResolver $resolver)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($code = $this->option('code')) {
            return $this->setDirection((string) $code);
        }

        return $this->listRows();
    }

    /**
     * Which answers come from UnsignedAdjustmentResolver rather than from here, so that
     * `php artisan` and PATCH /finances/adjustments/{id}/direction cannot disagree about
     * which rows are repairable. This remains the only writer that runs without the tenant
     * scope, which is how one operator clears a backlog across the whole install.
     */
    private function setDirection(string $code): int
    {
        $direction = CashFlowDirection::tryFrom(strtolower((string) $this->option('direction')));

        if (! $direction) {
            $this->error('Pass --direction=credit or --direction=debit.');

            return self::FAILURE;
        }

        $cashFlow = CashFlow::withoutGlobalScopes()
            ->where('transaction_code', $code)
            ->first();

        if (! $cashFlow) {
            $this->error("No cash flow found for transaction code [{$code}].");

            return self::FAILURE;
        }

        $refusal = $this->resolver->refusalFor($cashFlow);

        if ($refusal !== null) {
            // An already-signed row is not a failure: the requested end state already
            // holds, and re-running a repair script should say so rather than exit non-zero.
            $cashFlow->direction !== null
                ? $this->warn($refusal)
                : $this->error($refusal);

            return $cashFlow->direction !== null ? self::SUCCESS : self::FAILURE;
        }

        $this->resolver->apply($cashFlow, $direction);

        $this->info("[{$code}] set to {$direction->value}. It now moves the cash balance.");

        return self::SUCCESS;
    }

    private function listRows(): int
    {
        $query = CashFlow::withoutGlobalScopes()
            ->where('type', CashFlowType::Adjustment->value)
            ->whereNull('direction');

        if ($businessId = $this->option('business')) {
            $query->where('business_id', (int) $businessId);
        }

        $rows = $query->orderBy('business_id')->orderBy('id')->get();

        if ($rows->isEmpty()) {
            $this->info('No unsigned adjustments. Every adjustment records a direction.');

            return self::SUCCESS;
        }

        $this->warn(sprintf(
            '%d adjustment(s) carry no direction and are excluded from the cash balance.',
            $rows->count()
        ));

        $this->table(
            ['id', 'business', 'branch', 'transaction_code', 'amount', 'date', 'description'],
            $rows->map(fn ($row) => [
                $row->id,
                $row->business_id,
                $row->business_branch_id,
                $row->transaction_code,
                number_format((float) $row->amount, 2),
                $row->transaction_date?->toDateString(),
                Str::limit((string) $row->description, 40),
            ])->all()
        );

        $this->line('Decide each one, then record it:');
        $this->line('  php artisan duukaflow:finance:unsigned-adjustments --code=<code> --direction=credit|debit');

        return self::SUCCESS;
    }
}
