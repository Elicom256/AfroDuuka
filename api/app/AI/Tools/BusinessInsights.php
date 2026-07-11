<?php

namespace App\AI\Tools;

use App\AI\Tool;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

class BusinessInsights extends Tool
{
    public function name(): string
    {
        return 'business_insights';
    }

    public function description(): string
    {
        return 'Get general business insights and key metrics at a glance';
    }

    public function parameters(): array
    {
        return [];
    }

    public function handle(array $parameters): array
    {
        $totalProducts = Product::count();
        $activeProducts = Product::where('status', 'active')->count();
        $lowStock = Product::whereColumn('quantity', '<=', 'reorder_level')->where('quantity', '>', 0)->count();
        $outOfStock = Product::where('quantity', '<=', 0)->count();

        $todaySales = (float) Sale::whereDate('created_at', today())->where('status', 'completed')->sum('total_amount');
        $todaySalesCount = Sale::whereDate('created_at', today())->where('status', 'completed')->count();

        $monthSales = (float) Sale::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->where('status', 'completed')->sum('total_amount');
        $monthPurchases = (float) Purchase::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->where('status', 'completed')->sum('total_amount');

        $stockValue = (float) Product::select(DB::raw('COALESCE(SUM(quantity * cost_price), 0) as total'))->first()->total;

        return [
            'inventory' => [
                'total_products' => $totalProducts,
                'active_products' => $activeProducts,
                'low_stock' => $lowStock,
                'out_of_stock' => $outOfStock,
                'stock_value' => $stockValue,
            ],
            'sales' => [
                'today' => $todaySales,
                'today_transactions' => $todaySalesCount,
                'this_month' => $monthSales,
            ],
            'purchases' => [
                'this_month' => $monthPurchases,
            ],
            'summary' => "You have {$activeProducts} active products out of {$totalProducts} total. " .
                "{$lowStock} products need restocking and {$outOfStock} are out of stock. " .
                "Today's sales: {$todaySales} (" . ($todaySales > $monthPurchases ? 'profitable' : 'watch spending') . ').',
        ];
    }
}
