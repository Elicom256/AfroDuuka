<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Console\Command;

class SweepExpiredProducts extends Command
{
    protected $signature = 'inventory:sweep-expired';

    protected $description = 'Write off stock for products past their expiry date';

    public function handle(InventoryService $inventoryService): int
    {
        $expired = Product::whereNotNull('expiry_date')
            ->where('expiry_date', '<', now()->toDateString())
            ->where('quantity', '>', 0)
            ->get();

        $count = 0;

        foreach ($expired as $product) {
            try {
                $inventoryService->writeOff($product, $product->quantity, 'expired', 'Auto-detected expired stock');
                $product->update(['status' => 'expired']);
                $count++;
                $this->info("Wrote off {$product->quantity} units of {$product->name}");
            } catch (\Throwable $e) {
                $this->error("Failed to write off {$product->name}: {$e->getMessage()}");
            }
        }

        $this->info("Expiry sweep complete. {$count} products written off.");

        return self::SUCCESS;
    }
}
