<?php

namespace App\Services;

use Carbon\Carbon;

class AnalyticsTrendHelper
{
    /**
     * Get number of days based on period
     */
    public function getDaysFromPeriod(string $period): int
    {
        return match (strtolower($period)) {
            'today' => 1,
            'last_7_days' => 7,
            'last_30_days' => 30,
            'this_month' => Carbon::now()->daysInMonth,
            'last_month' => Carbon::now()->subMonth()->daysInMonth,
            default => 7,
        };
    }

    /**
     * Fill missing dates with zero values for clean chart
     *
     * Only date/amount/count are hardcoded. Any further keys a caller puts on its
     * trend points are carried through, defaulting to zero on empty days. Callers
     * build different shapes here (sales carries items, returns carries quantity),
     * and dropping the extras would quietly delete those series before they ever
     * reach the client.
     */
    public function fillMissingDates($data, int $days = 7)
    {
        $filled = [];
        $startDate = Carbon::now()->subDays($days - 1);

        for ($i = 0; $i < $days; $i++) {
            $filled[] = $this->trendPoint($data, $startDate->copy()->addDays($i)->format('M d'));
        }

        return $filled;
    }

    /**
     * Same as fillMissingDates, but anchored to an explicit range instead of a day
     * count ending today.
     *
     * Needed for calendar periods. On the 5th of a 31 day month, a day count anchored
     * on today reaches back into the previous month, so a "this month" trend would
     * open with days that belong to last month.
     */
    public function fillMissingCalendarDates($data, Carbon $start, Carbon $end)
    {
        $filled = [];

        for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
            $filled[] = $this->trendPoint($data, $cursor->format('M d'));
        }

        return $filled;
    }

    /**
     * One trend point, defaulting the base keys to zero and carrying through whatever
     * extra keys the caller attached to the real data.
     */
    private function trendPoint($data, string $dateLabel): array
    {
        $existing = $data->firstWhere('date', $dateLabel);

        $point = [
            'date' => $dateLabel,
            'amount' => $existing['amount'] ?? 0,
            'count' => $existing['count'] ?? 0,
        ];

        $extraKeys = collect($existing ?? [])
            ->keys()
            ->reject(fn ($key) => in_array($key, ['date', 'amount', 'count'], true));

        foreach ($extraKeys as $key) {
            $point[$key] = $existing[$key] ?? 0;
        }

        return $point;
    }

    /**
     * Resolve a period to an absolute window plus the number of trend points in it.
     *
     * Rolling periods keep the existing day-count behaviour. Month periods are
     * calendar ranges instead, because a day count anchored on today drifts: the 5th
     * of a 31 day month minus 30 days lands in the previous month, which made a
     * "last month" report cover part of the current month and therefore never settle.
     *
     * Note labels are "M d", so a window is only unambiguous within one calendar year.
     * Year periods are left to the day-count path rather than silently gaining
     * duplicated labels.
     *
     * @return array{start: Carbon, end: Carbon, days: int, calendar: bool}
     */
    public function resolvePeriodWindow(string $period): array
    {
        $period = strtolower($period);

        if (in_array($period, ['this_month', 'last_month'], true)) {
            $dates = $this->getPeriodDates($period);
            $start = Carbon::parse($dates['start'])->startOfDay();
            $end = Carbon::parse($dates['end'])->endOfDay();

            return [
                'start' => $start,
                'end' => $end,
                'days' => (int) $start->diffInDays($end) + 1,
                'calendar' => true,
            ];
        }

        $days = $this->getDaysFromPeriod($period);

        return [
            'start' => $period === 'today' ? Carbon::today() : Carbon::now()->subDays($days - 1)->startOfDay(),
            'end' => Carbon::now()->endOfDay(),
            'days' => $days,
            'calendar' => false,
        ];
    }

    //
    public function getPeriodDates(string $period): array
    {
        return match (strtolower($period)) {

            'last_7_days' => [
                'start' => Carbon::now()->subDays(7),
                'end' => Carbon::now(),
            ],

            'last_30_days' => [
                'start' => Carbon::now()->subDays(30),
                'end' => Carbon::now(),
            ],

            'this_month' => [
                'start' => Carbon::now()->startOfMonth(),
                'end' => Carbon::now()->endOfMonth(),
            ],

            'last_month' => [
                'start' => Carbon::now()->subMonth()->startOfMonth(),
                'end' => Carbon::now()->subMonth()->endOfMonth(),
            ],
            'this_year' => [
                'start' => Carbon::now()->startOfYear(),
                'end' => Carbon::now()->endOfYear(),
            ],

            'last_year' => [
                'start' => Carbon::now()->subYear()->startOfYear(),
                'end' => Carbon::now()->subYear()->endOfYear(),
            ],

            default => [
                'start' => Carbon::now()->subDays(7),
                'end' => Carbon::now(),
            ],
        };
    }
}
