<?php

namespace App\Http\Controllers;

use App\Http\Requests\CloseCashDrawerRequest;
use App\Http\Requests\OpenCashDrawerRequest;
use App\Models\CashDrawerSession;
use App\Services\CashDrawerService;
use Illuminate\Support\Facades\Auth;

class CashDrawerController extends Controller
{
    public function __construct(private CashDrawerService $cashDrawerService)
    {
    }

    public function open(OpenCashDrawerRequest $request)
    {
        $data = $request->validated();
        $session = $this->cashDrawerService->open(
            Auth::user(),
            (float) $data['opening_cash'],
            isset($data['business_branch_id']) ? (int) $data['business_branch_id'] : null,
            (float) ($data['allowed_variance'] ?? 0),
        );

        return response()->json(['message' => 'Cash drawer opened.', 'data' => $session->fresh()], 201);
    }

    public function close(CloseCashDrawerRequest $request, CashDrawerSession $session)
    {
        $closed = $this->cashDrawerService->close(
            Auth::user(),
            $session,
            (float) $request->validated()['counted_cash'],
        );

        return response()->json(['message' => 'Cash drawer closed.', 'data' => $closed]);
    }

    public function show(CashDrawerSession $session)
    {
        return response()->json(['data' => $session->load(['branch', 'openedBy', 'closedBy'])]);
    }
}
