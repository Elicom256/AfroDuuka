<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateTodoRequest extends FormRequest
{
    /**
     * Deliberately not the whole story.
     *
     * TodoController::update() calls $this->authorize('update', $todo), which runs
     * TodoPolicy — the business check and the owner-or-manager rule both live there.
     * Returning true here only means "let the request reach the controller"; a request
     * that satisfies this and fails the policy is still rejected.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:2000',
            'date' => 'nullable|date',
            'status' => ['sometimes', Rule::in(['completed', 'undone', 'canceled'])],
        ];
    }
}
