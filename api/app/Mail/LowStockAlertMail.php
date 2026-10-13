<?php

namespace App\Mail;

use App\Models\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LowStockAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Business $business,
        public readonly array $products,
        public readonly string $branchName,
    ) {}

    public function build(): static
    {
        return $this->subject('Low Stock Alert - ' . $this->branchName)
            ->view('mail.low-stock-alert', [
                'business' => $this->business,
                'products' => $this->products,
                'branchName' => $this->branchName,
            ]);
    }
}