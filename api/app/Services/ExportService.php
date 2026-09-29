<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class ExportService
{
    public function export(string $type, array $filters = [])
    {
        return match ($type) {
            'products' => $this->exportProducts(),
            'sales' => $this->exportSales($filters),
            'purchases' => $this->exportPurchases($filters),
            'customers' => $this->exportCustomers(),
            'suppliers' => $this->exportSuppliers(),
            default => throw new \InvalidArgumentException("Unknown export type: {$type}"),
        };
    }

    private function exportProducts()
    {
        $products = Product::with('productCategory')
            ->where('business_branch_id', Auth::user()->business_branch_id)
            ->get();

        $headers = ['ID', 'Name', 'SKU', 'Category', 'Quantity', 'Reorder Level', 'Cost Price', 'Selling Price', 'Status'];
        $rows = $products->map(fn ($p) => [
            $p->id, $p->name, $p->sku, $p->productCategory?->name, $p->quantity,
            $p->reorder_level, $p->cost_price, $p->selling_price, $p->status,
        ]);

        return $this->streamCsv('products', $headers, $rows);
    }

    private function exportSales(array $filters)
    {
        $query = Sale::with(['saleItems.product', 'customer'])
            ->where('business_branch_id', Auth::user()->business_branch_id);

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $sales = $query->get();

        $headers = ['Sale ID', 'Date', 'Customer', 'Items', 'Subtotal', 'Tax', 'Total', 'Status'];
        $rows = $sales->map(fn ($s) => [
            $s->id, $s->created_at->format('Y-m-d'), $s->customer?->name ?? 'Walk-in',
            $s->saleItems->sum('quantity'), $s->subtotal, $s->tax_amount, $s->total_amount, $s->status,
        ]);

        return $this->streamCsv('sales', $headers, $rows);
    }

    private function exportPurchases(array $filters)
    {
        $query = Purchase::with(['purchaseItems.product', 'supplier'])
            ->where('business_branch_id', Auth::user()->business_branch_id);

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $purchases = $query->get();

        $headers = ['Purchase ID', 'Date', 'Supplier', 'Items', 'Total', 'Status'];
        $rows = $purchases->map(fn ($p) => [
            $p->id, $p->created_at->format('Y-m-d'), $p->supplier?->name,
            $p->purchaseItems->sum('quantity'), $p->total_amount, $p->status,
        ]);

        return $this->streamCsv('purchases', $headers, $rows);
    }

    private function exportCustomers()
    {
        $customers = Customer::where('business_id', Auth::user()->business_id)->get();

        $headers = ['ID', 'Name', 'Phone', 'Email', 'Location', 'Created At'];
        $rows = $customers->map(fn ($c) => [
            $c->id, $c->name, $c->phone, $c->email, $c->location, $c->created_at->format('Y-m-d'),
        ]);

        return $this->streamCsv('customers', $headers, $rows);
    }

    private function exportSuppliers()
    {
        $suppliers = Supplier::where('business_id', Auth::user()->business_id)->get();

        $headers = ['ID', 'Name', 'Phone', 'Email', 'Created At'];
        $rows = $suppliers->map(fn ($s) => [
            $s->id, $s->name, $s->phone, $s->email, $s->created_at->format('Y-m-d'),
        ]);

        return $this->streamCsv('suppliers', $headers, $rows);
    }

    private function streamCsv(string $filename, array $headers, $rows)
    {
        $callback = function () use ($headers, $rows) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, $headers);
            foreach ($rows as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$filename}-" . date('Y-m-d') . '.csv',
        ]);
    }
}
