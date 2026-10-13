<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Services\Mail\BusinessVerificationMailService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmailVerificationController extends Controller
{
    public function __construct(
        private readonly BusinessVerificationMailService $verificationService,
    ) {}

    /**
     * Verify business email address via token.
     */
    public function verify(Request $request): JsonResponse
    {
        $token = $request->query('token');

        if (! $token) {
            return response()->json([
                'message' => 'Verification token is required',
            ], 400);
        }

        $business = $this->verificationService->verify($token);

        if (! $business) {
            return response()->json([
                'message' => 'Invalid or expired verification token',
            ], 404);
        }

        return response()->json([
            'message' => 'Email verified successfully. Your business is now active.',
            'business' => $business->load('country'),
        ], 200);
    }

    /**
     * Resend verification email.
     */
    public function resend(Request $request): JsonResponse
    {
        $business = $request->user()->business;

        if (! $business) {
            return response()->json([
                'message' => 'No business found for this user',
            ], 404);
        }

        if ($business->email_verified_at) {
            return response()->json([
                'message' => 'Email is already verified',
            ], 200);
        }

        $this->verificationService->resend($business);

        return response()->json([
            'message' => 'Verification email sent',
        ], 200);
    }
}