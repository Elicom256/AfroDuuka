<?php

namespace App\ValueObjects;

/**
 * A monthly performance report, normalised out of a notification payload.
 *
 * Exists as a value object rather than as array juggling inside the PDF builder for one
 * reason: the figures arrive as *formatted strings*. The code that computes them runs
 * them through number_format(), so the payload holds "1,234.56", and (float) "1,234.56"
 * is 1.0. That is a silent factor of a thousand on a revenue figure, it survives every
 * type check that only asks "is this numeric?", and the only place it becomes visible is
 * a wrong total printed in a PDF that nobody re-derives by hand.
 *
 * So the parsing is here, once, where it can be tested against the exact strings the
 * real payload contains.
 *
 * The payload contract, which the monthly report trigger has to match exactly:
 *
 *   business_name       string, the tenant's trading name
 *   period              string, e.g. "August 2026". Also the dedupe identity.
 *   currency            string, 3 letters. Defaults to UGX.
 *   total_sales         number or formatted string
 *   total_purchases     number or formatted string
 *   total_expenses      number or formatted string
 *   total_profit_loss   number or formatted string
 *   number_of_sales     int
 *   number_of_purchases int
 *   branches            optional list of {name, sales, purchases, expenses, profit_loss}
 *
 * Note the asymmetry, which is not a typo: the four totals are prefixed `total_` and
 * the two counts are prefixed `number_of_`, but the branch rows drop the prefix
 * entirely. A mismatch here is silent — a report missing its headline figure still
 * renders, with "not recorded" where the profit should be, and nothing anywhere reports
 * an error. hasFigures() only catches the case where *every* figure is missing.
 */
final class MonthlyReport
{
    public const FIGURES = ['sales', 'purchases', 'expenses', 'profit_loss'];

    public const COUNT_FIGURES = ['sales', 'purchases'];

    /**
     * @param  array<string, float|null>  $figures
     * @param  array<string, int|null>  $counts
     * @param  array<int, array<string, mixed>>  $branches
     */
    private function __construct(
        public readonly string $businessName,
        public readonly string $period,
        public readonly string $currency,
        public readonly array $figures,
        public readonly array $counts,
        public readonly array $branches,
    ) {}

    /**
     * @param  array<string, mixed>  $values  A notification_deliveries payload.
     */
    public static function fromPayload(array $values): self
    {
        $figures = [];

        foreach (self::FIGURES as $figure) {
            $figures[$figure] = self::number($values['total_'.$figure] ?? null);
        }

        $counts = [];

        foreach (self::COUNT_FIGURES as $figure) {
            $counts[$figure] = self::integer($values['number_of_'.$figure] ?? null);
        }

        return new self(
            businessName: (string) ($values['business_name'] ?? config('app.name')),
            period: (string) ($values['period'] ?? ''),
            currency: (string) ($values['currency'] ?? 'UGX'),
            figures: $figures,
            counts: $counts,
            branches: self::branches($values['branches'] ?? null),
        );
    }

    /**
     * Whether there is anything worth printing.
     *
     * An all-null report is not a report with zeroes in it, it is a report whose figures
     * were never supplied. Attaching one would read as a rendering fault rather than as
     * an absence, so the caller sends the email without a file instead.
     */
    public function hasFigures(): bool
    {
        return array_filter($this->figures, fn (?float $v) => $v !== null) !== [];
    }

    /**
     * The filename stem, e.g. "august-2026" from "August 2026".
     */
    public function slug(): string
    {
        return trim(strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $this->period)), '-');
    }

    /**
     * The shape the Blade view reads.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'business_name' => $this->businessName,
            'period' => $this->period,
            'currency' => $this->currency,
            'figures' => $this->figures,
            'counts' => $this->counts,
            'branches' => $this->branches,
        ];
    }

    /**
     * Coerce a figure to a float, or null when it is not one.
     */
    private static function number(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if ($value === null || is_bool($value) || is_array($value)) {
            return null;
        }

        // Thousands separators, stray spaces and the non-breaking space number_format
        // emits under some locales all have to go before the string can be read as a
        // number.
        $cleaned = str_replace([',', ' ', "\u{00a0}"], '', (string) $value);

        return is_numeric($cleaned) ? (float) $cleaned : null;
    }

    private static function integer(mixed $value): ?int
    {
        $number = self::number($value);

        return $number === null ? null : (int) $number;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function branches(mixed $branches): array
    {
        if (! is_array($branches)) {
            return [];
        }

        $rows = [];

        foreach ($branches as $branch) {
            if (! is_array($branch) || blank($branch['name'] ?? null)) {
                continue;
            }

            $row = ['name' => (string) $branch['name']];

            foreach (self::FIGURES as $figure) {
                $row[$figure] = self::number($branch[$figure] ?? null);
            }

            $rows[] = $row;
        }

        return $rows;
    }
}
