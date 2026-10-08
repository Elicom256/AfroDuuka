<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Support\Tenant\EffectiveBranchScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExportService
{
    /**
     * A customer or supplier is named by its company when it has one, otherwise by the
     * person behind it.
     *
     * Neither table has a `name` column -- only `company_name` and a `user_id` -- so reading
     * `->name` off one returned null and the export carried a blank column rather than an
     * error. That is worse than failing: the file looked like it had worked.
     */
    private function partyName(?string $companyName, ?User $user): string
    {
        return $companyName ?: trim(implode(' ', array_filter([$user?->firstname, $user?->lastname])));
    }

    public function export(string $type, array $filters = [])
    {
        return match ($type) {
            'products' => $this->exportProducts(),
            'sales' => $this->exportSales($filters),
            'purchases' => $this->exportPurchases($filters),
            'customers' => $this->exportCustomers(),
            'suppliers' => $this->exportSuppliers(),
            // A bad path segment is a client error, not a server one. Throwing a plain
            // InvalidArgumentException here surfaced as a 500, which reads as "the export
            // is broken" when the caller simply asked for a type that does not exist.
            default => throw new NotFoundHttpException("Unknown export type: {$type}"),
        };
    }

    private function exportProducts()
    {
        $resolved = EffectiveBranchScope::branchesFor(Auth::user());
        $branchIds = $resolved !== null ? $resolved[1] : null;

        $query = Product::with('productCategory');
        if ($branchIds !== null) {
            $query->whereIn('business_branch_id', $branchIds);
        }
        $products = $query->get();

        $headers = ['ID', 'Name', 'SKU', 'Category', 'Quantity', 'Reorder Level', 'Cost Price', 'Selling Price', 'Status'];
        $rows = $products->map(fn ($p) => [
            $p->id, $p->name, $p->sku, $p->productCategory?->name, $p->quantity,
            $p->reorder_level, $p->cost_price, $p->selling_price, $p->status,
        ]);

        return $this->streamCsv('products', $headers, $rows);
    }

    private function exportSales(array $filters)
    {
        $resolved = EffectiveBranchScope::branchesFor(Auth::user());
        $branchIds = $resolved !== null ? $resolved[1] : null;

        $query = Sale::with(['saleItems.product', 'customer.user']);
        if ($branchIds !== null) {
            $query->whereIn('business_branch_id', $branchIds);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $sales = $query->get();

        $headers = ['Sale ID', 'Date', 'Customer', 'Items', 'Subtotal', 'Tax', 'Total', 'Status'];
        $rows = $sales->map(fn ($s) => [
            $s->id, $s->created_at->format('Y-m-d'), $this->partyName($s->customer?->company_name, $s->customer?->user) ?: 'Walk-in',
            $s->saleItems->sum('quantity'), $s->subtotal, $s->tax_amount, $s->total_amount, $s->status,
        ]);

        return $this->streamCsv('sales', $headers, $rows);
    }

    private function exportPurchases(array $filters)
    {
        $resolved = EffectiveBranchScope::branchesFor(Auth::user());
        $branchIds = $resolved !== null ? $resolved[1] : null;

        $query = Purchase::with(['purchaseItems.product', 'supplier.user']);
        if ($branchIds !== null) {
            $query->whereIn('business_branch_id', $branchIds);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $purchases = $query->get();

        $headers = ['Purchase ID', 'Date', 'Supplier', 'Items', 'Total', 'Status'];
        $rows = $purchases->map(fn ($p) => [
            $p->id, $p->created_at->format('Y-m-d'), $this->partyName($p->supplier?->company_name, $p->supplier?->user),
            $p->purchaseItems->sum('quantity'), $p->total_amount, $p->status,
        ]);

        return $this->streamCsv('purchases', $headers, $rows);
    }

    private function exportCustomers()
    {
        $customers = Customer::with('user')
            ->where('business_id', Auth::user()->business_id)
            ->get();

        $headers = ['ID', 'Company', 'Contact Name', 'Phone', 'Email', 'Status', 'Created At'];
        $rows = $customers->map(fn ($c) => [
            $c->id,
            $c->company_name,
            trim(implode(' ', array_filter([$c->user?->firstname, $c->user?->lastname]))),
            $c->user?->phone,
            $c->user?->email,
            $c->status,
            $c->created_at->format('Y-m-d'),
        ]);

        return $this->streamCsv('customers', $headers, $rows);
    }

    private function exportSuppliers()
    {
        $suppliers = Supplier::with('user')
            ->where('business_id', Auth::user()->business_id)
            ->get();

        $headers = ['ID', 'Company', 'Contact Name', 'Phone', 'Email', 'Status', 'Created At'];
        $rows = $suppliers->map(fn ($s) => [
            $s->id,
            $s->company_name,
            trim(implode(' ', array_filter([$s->user?->firstname, $s->user?->lastname]))),
            $s->user?->phone,
            $s->user?->email,
            $s->status,
            $s->created_at->format('Y-m-d'),
        ]);

        return $this->streamCsv('suppliers', $headers, $rows);
    }

    private function streamCsv(string $filename, array $headers, $rows)
    {
        $callback = function () use ($headers, $rows) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, $headers);
            foreach ($rows as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$filename}-".date('Y-m-d').'.csv',
        ]);
    }
}
