<?php

namespace App\Http\Controllers;

use App\AI\Agent;
use App\AI\ToolRegistry;
use App\Support\Auth\RolePermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiController extends Controller
{
    protected Agent $agent;

    public function __construct()
    {
        $registry = new ToolRegistry();
        $this->agent = new Agent($registry);
    }

    /**
     * Talk to the agent.
     *
     * Held to elevated roles rather than left to any signed-in account: each call is an
     * unmetered round trip to a model provider that bills per token, and the tool
     * registry exposes business data to whatever the model is asked. Tenant scoping
     * keeps it inside one business, but it does not decide who inside that business may
     * spend provider budget. Note the review scope note defers third-party integration
     * work — this gate is a local authorization fix, not integration work.
     */
    public function chat(Request $request): JsonResponse
    {
        abort_unless(RolePermissions::isElevated($request->user()), 403, 'You cannot use the assistant.');

        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $result = $this->agent->handle($request->input('message'));

        return response()->json($result);
    }

    public function tools(): JsonResponse
    {
        return response()->json([
            'tools' => $this->agent->definitions(),
        ]);
    }
}
