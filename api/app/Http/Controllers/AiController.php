<?php

namespace App\Http\Controllers;

use App\AI\Agent;
use App\AI\ToolRegistry;
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

    public function chat(Request $request): JsonResponse
    {
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
