<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Envelope validation for POST /sync/batch.
 *
 * Only the batch envelope is validated here. Each operation's payload is validated
 * by the request class that already owns that contract (PosCheckoutRequest for a
 * sale, StoreCustomerRequest for a customer, …) inside the type handler, so the
 * offline and online paths share one definition of every payload's rules.
 *
 * The batch cap bounds a single request's work: processing is synchronous and each
 * operation opens at least two transactions, so an unbounded array would let one
 * client monopolise a worker and turn a retry into a very long request.
 */
class SyncBatchRequest extends FormRequest
{
    public const MAX_OPERATIONS = 50;

    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'operations' => ['required', 'array', 'min:1', 'max:'.self::MAX_OPERATIONS],

            'operations.*.operation_id' => ['required', 'uuid'],
            'operations.*.type' => ['required', 'string', Rule::in(['sale', 'inventory', 'customer', 'todo'])],
            'operations.*.payload' => ['required', 'array'],
            // Client timestamp for audit context only — never used for ordering.
            'operations.*.created_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'operations.required' => 'At least one operation is required.',
            'operations.max' => 'A batch may contain at most '.self::MAX_OPERATIONS.' operations.',
            'operations.*.operation_id.uuid' => 'Each operation requires a UUID operation_id.',
        ];
    }
}
