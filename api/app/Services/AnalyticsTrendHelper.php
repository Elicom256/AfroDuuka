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
            'last_7_days'  => 7,
            'last_30_days' => 30,
            'this_month'   => Carbon::now()->daysInMonth,
            'last_month'   => Carbon::now()->subMonth()->daysInMonth,
            default        => 7,
        };
    }

    /**
     * Fill missing dates with zero values for clean chart
     */
    public function fillMissingDates($data, int $days = 7)
    {
        $filled = [];
        $startDate = Carbon::now()->subDays($days - 1);

        for ($i = 0; $i < $days; $i++) {
            $currentDate = $startDate->copy()->addDays($i);
            $dateLabel = $currentDate->format('M d');

            $existing = $data->firstWhere('date', $dateLabel);

            $filled[] = [
                'date'   => $dateLabel,
                'amount' => $existing['amount'] ?? 0,
                'count'  => $existing['count'] ?? 0,
            ];
        }

        return $filled;
    }
}