<?php

namespace App\Http\Requests;

use App\Models\Salary;
use App\Support\Tenant\EffectiveBranchScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreSalaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        $resolved = EffectiveBranchScope::validationBranchesFor(Auth::user());
        $branchIds = $resolved === null ? null : $resolved[1];

        // Roles are business-level (no branch column), so only the tenant is scoped.
        // A bare `exists` would let a salary be created against another business's role.
        $roleRule = Rule::exists('roles', 'id')->where('business_id', Auth::user()?->business_id);

        $branchRule = Rule::exists('business_branches', 'id')->where('business_id', Auth::user()?->business_id);
        if ($branchIds !== null) {
            $branchRule->whereIn('id', $branchIds);
        }

        return [
            'role_id' => ['required', 'integer', $roleRule],
            'amount' => ['required', 'numeric', 'min:0'],
            'period' => ['required', Rule::in(Salary::PERIODS)],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'business_branch_id' => ['nullable', 'integer', $branchRule],
        ];
    }
}
