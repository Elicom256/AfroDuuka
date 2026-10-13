<?php

namespace App\Services\Mail;

use App\Mail\MonthlyPerformanceMail;
use App\Models\Business;
use Illuminate\Support\Facades\Mail;

class MonthlyPerformanceMailService
{
    public function send(Business $business, string $period, string $reportUrl, ?string $pdfPath, array $recipientEmails): void
    {
        foreach ($recipientEmails as $email) {
            Mail::to($email)
                ->send(new MonthlyPerformanceMail($business, $period, $reportUrl, $pdfPath));
        }
    }
}