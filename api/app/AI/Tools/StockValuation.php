<?php

namespace App\AI\Tools;

use App\AI\Tool;
use App\Models\BusinessBranchProduct;
use Illuminate\Support\Facades\DB;

class StockValuation extends Tool
{
    public function name(): string
    {
        return 'stock_valuation';
    }

    public function description(): string
    {
        return 'Get the total value of current inventory based on cost price';
    }

    public function parameters(): array
    {
        return [
            'branch_id' => [
                'type' => 'integer',
                'description' => 'Filter by branch ID (optional)',
            ],
        ];
    }

    public function handle(array $parameters): array
    {
        $query = BusinessBranchProduct::where('quantity', '>', 0);

        if (!empty($parameters['branch_id'])) {
            $query->where('business_branch_id', $parameters['branch_id']);
        }

        $valuation = (clone $query)->select(
            DB::raw('COALESCE(SUM(quantity * cost_price), 0) as total_cost_value'),
            DB::raw('COALESCE(SUM(quantity * price), 0) as total_retail_value'),
            DB::raw('COUNT(*) as product_count'),
            DB::raw('COALESCE(SUM(quantity), 0) as total_units')
        )->first();

        $byBranch = (clone $query)
            ->select(
                'business_branch_id',
                DB::raw('COALESCE(SUM(quantity * cost_price), 0) as cost_value'),
                DB::raw('COALESCE(SUM(quantity), 0) as units')
            )
            ->groupBy('business_branch_id')
            ->with('businessBranch')
            ->get()
            ->map(fn ($item) => [
                'branch_id' => $item->business_branch_id,
                'cost_value' => (float) $item->cost_value,
                'units' => (int) $item->units,
            ]);

        return [
            'total_cost_value' => (float) $valuation->total_cost_value,
            'total_retail_value' => (float) $valuation->total_retail_value,
            'potential_profit' => (float) ($valuation->total_retail_value - $valuation->total_cost_value),
            'product_count' => (int) $valuation->product_count,
            'total_units' => (int) $valuation->total_units,
            'by_branch' => $byBranch,
        ];
    }
}
