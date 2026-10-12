<?php

namespace App\Http\Middleware;

use App\Support\Auth\RolePermissions;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Confines the till to staff.
 *
 * The POS routes were declared under auth:sanctum with no role check, and
 * RequireBusiness only asks whether the caller has a business_id. Both counterparties —
 * customer and supplier — hold a User row carrying that same business_id:
 * StoreCustomerRequest assigns the 'customer' role alongside the caller's own tenant,
 * because a customer has to belong to the business whose books it appears on. They
 * therefore satisfied every middleware in the chain and could reach the till, which meant
 * ringing up sales, taking payment, decrementing stock and writing cash-flow rows.
 *
 * A customer is a party on the business's books, not someone who works it, so this is
 * authority a counterparty was never meant to hold. It is worth a separate capability
 * rather than reusing an existing one because canModifyStock() is the closest match and
 * would wrongly include Procurement.
 */
class RequireTillAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            RolePermissions::canOperateTill(Auth::user()),
            403,
            'The till is available to staff accounts.'
        );

        return $next($request);
    }
}
