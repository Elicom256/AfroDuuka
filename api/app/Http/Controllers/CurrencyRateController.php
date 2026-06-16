<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCurrencyRateRequest;
use App\Http\Requests\UpdateCurrencyRateRequest;
use App\Models\CurrencyRate;
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
        $rate = CurrencyRate::create($request->validated());
        return response()->json(['message' => 'Currency rate created', 'data' => $rate], 201);
    }

    public function show(CurrencyRate $currencyRate)
    {
        return response()->json(['message' => 'Fetched currency rate', 'data' => $currencyRate]);
    }

    public function update(UpdateCurrencyRateRequest $request, CurrencyRate $currencyRate)
    {
        $currencyRate->update($request->validated());
        return response()->json(['message' => 'Currency rate updated', 'data' => $currencyRate]);
    }

    public function destroy(CurrencyRate $currencyRate)
    {
        $currencyRate->delete();
        return response()->json(['message' => 'Currency rate deleted']);
    }
}
