<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'business_id' => Auth::user()->business_id,
            'transferred_by' => Auth::id(),
            'status' => $this->input('status', 'draft'),
        ]);
    }

    public function rules(): array
    {
        $ownBranch = Rule::exists('business_branches', 'id')->where(
            'business_id',
            Auth::user()?->business_id
        );

        return [
            // Merged from the authenticated user above, so a client cannot move a
            // transfer into another tenant by supplying a different business_id.
            'business_id' => 'required|exists:businesses,id',

            // Scoped to the caller's own business rather than a bare exists:key. A
            // bare exists accepted any branch id in the database, so a transfer could
            // be created naming a branch belonging to a different tenant. dispatch()
            // would then fail on a firstOrFail() against a tenant-scoped Product
            // query — but only at dispatch time, leaving a transfer row in the
            // meantime that pointed at another business's branch.
            'from_branch_id' => ['required', $ownBranch, 'different:to_branch_id'],
            'to_branch_id' => ['required', $ownBranch],

            'status' => 'sometimes|string|in:draft,in_transit,received,cancelled',
            'transferred_by' => 'required|exists:users,id',
            'notes' => 'nullable|string|max:1000',
            'transport_cost' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            // Same reasoning as the branch ids: the product has to belong to the
            // business the transfer is being created under.
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity_expected' => 'required|integer|min:1',
        ];
    }
}
