<?php

namespace App\Services\Mail;

use App\Mail\LowStockAlertMail;
use App\Models\Business;
use Illuminate\Support\Facades\Mail;

class LowStockAlertMailService
{
    public function send(Business $business, array $products, string $branchName, array $recipientEmails): void
    {
        foreach ($recipientEmails as $email) {
            Mail::to($email)
                ->send(new LowStockAlertMail($business, $products, $branchName));
        }
    }
}