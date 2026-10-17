<?php

namespace App\Services\Sync;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;

/**
 * Runs an existing FormRequest against a sync payload instead of the HTTP request.
 *
 * The operation types being replayed already have FormRequests with the domain's
 * validation rules, authorization, and pre-validation merges (branch/currency
 * defaults, payment-coverage checks). Re-instantiating those requests here keeps a
 * single definition of each payload contract: an offline sale is validated exactly
 * like `POST /pos/checkout`, not by a copy of its rules that can drift.
 *
 * The instance is built from a synthetic POST request carrying only the operation
 * payload, then prepared the same way the container prepares a resolved FormRequest
 * (container + redirector) before `validateResolved()` runs prepare/authorize/validate.
 * A failed payload throws ValidationException, an unauthorized one AuthorizationException —
 * both are classified by the dispatcher into per-operation results.
 */
class ValidatesPayload
{
    /**
     * @param  class-string<FormRequest>  $requestClass
     * @return array<string, mixed> the FormRequest's validated data
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public static function run(string $requestClass, array $payload): array
    {
        /** @var FormRequest $request */
        $request = $requestClass::createFrom(
            Request::create('/api/sync/batch', 'POST', $payload)
        );

        $request->setContainer(app())->setRedirector(app(Redirector::class));
        // A synthetic request has no user resolver. FormRequests that call $this->user()
        // (catalogue authorization, branch defaults) must see the authenticated account.
        $request->setUserResolver(static fn () => Auth::user());
        $request->validateResolved();

        return $request->validated();
    }
}
