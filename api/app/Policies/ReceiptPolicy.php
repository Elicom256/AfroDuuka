<?php

namespace App\Policies;

use App\Models\Receipt;
use App\Models\User;

class ReceiptPolicy
{
    /**
     * Receipts are the customer-facing half of a sale: amounts, payment method and
     * line items. Reads stay open to any tenant user because the counter, the sales
     * list and the customer-credit screen all need them, and BaseModel's business
     * scope is what confines a caller to their own receipts.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Receipt $receipt): bool
    {
        return true;
    }

    /**
     * PDF rendering is a view over the same data, so it answers to the same rule —
     * but it is called out explicitly because it is the one receipt endpoint that
     * returns rendered customer PII (name, items, totals) rather than JSON, and
     * because it reads the business logo off disk.
     */
    public function print(User $user, Receipt $receipt): bool
    {
        return true;
    }
}