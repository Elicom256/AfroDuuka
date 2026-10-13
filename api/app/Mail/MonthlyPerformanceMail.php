<?php

namespace App\Mail;

use App\Models\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MonthlyPerformanceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Business $business,
        public readonly string $period,
        public readonly string $reportUrl,
        public readonly ?string $pdfPath = null,
    ) {}

    public function build(): static
    {
        $mail = $this->subject('Your monthly performance report for ' . $this->period)
            ->view('mail.report.monthly', [
                'business' => $this->business,
                'period' => $this->period,
                'reportUrl' => $this->reportUrl,
            ]);

        if ($this->pdfPath) {
            $mail->attach($this->pdfPath, [
                'as' => 'monthly-report-' . $this->period . '.pdf',
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }
}