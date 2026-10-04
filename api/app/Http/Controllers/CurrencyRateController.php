<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCurrencyRateRequest;
use App\Http\Requests\UpdateCurrencyRateRequest;
use App\Models\CurrencyRate;
use App\Support\Auth\RolePermissions;
use Illuminate\Support\Facades\Auth;

/**
 * Manages exchange rates for multi-currency operations.
 */
class CurrencyRateController extends Controller
{
    public function index()
    {
        $rates = CurrencyRate::where('business_id', Auth::user()->business_id)
            ->where(function ($q) {
                $q->whereNull('valid_to')->orWhere('valid_to', '>=', now());
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['message' => 'Fetched currency rates', 'data' => $rates]);
    }

    public function store(StoreCurrencyRateRequest $request)
    {
        abort_unless(RolePermissions::canManagePaymentConfig($request->user()), 403, 'You cannot manage currency rates.');

        $rate = CurrencyRate::create($request->validated());
        return response()->json(['message' => 'Currency rate created', 'data' => $rate], 201);
    }

    public function show(CurrencyRate $currencyRate)
    {
        return response()->json(['message' => 'Fetched currency rate', 'data' => $currencyRate]);
    }

    /**
     * Every multi-currency total and report derives from this row, so writing one is
     * a pricing decision rather than a floor action. Reads stay open.
     */
    public function update(UpdateCurrencyRateRequest $request, CurrencyRate $currencyRate)
    {
        abort_unless(RolePermissions::canManagePaymentConfig($request->user()), 403, 'You cannot manage currency rates.');

        $currencyRate->update($request->validated());
        return response()->json(['message' => 'Currency rate updated', 'data' => $currencyRate]);
    }

    public function destroy(CurrencyRate $currencyRate)
    {
        $currencyRate->delete();
        return response()->json(['message' => 'Currency rate deleted']);
    }
}
