<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Services\Mail\BusinessVerificationMailService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class EmailVerificationController extends Controller
{
    public function __construct(
        private readonly BusinessVerificationMailService $verificationService,
    ) {}

    /**
     * Verify business email address via token.
     * Returns JSON for API clients, redirects for browser clients.
     */
    public function verify(Request $request): JsonResponse|RedirectResponse
    {
        $token = $request->query('token');

        if (! $token) {
            return $this->errorResponse('Verification token is required', 400);
        }

        $business = $this->verificationService->verify($token);

        if (! $business) {
            return $this->errorResponse('Invalid or expired verification token', 404);
        }

        // If request expects JSON (API client), return JSON
        if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
            return response()->json([
                'message' => 'Email verified successfully. Your business is now active.',
                'business' => $business->load('country'),
            ], 200);
        }

        // Browser client: redirect to frontend onboarding/success page
        $frontendUrl = config('app.frontend_url', config('app.url'));
        $redirectUrl = $frontendUrl . '/onboarding?verified=1';

        return redirect($redirectUrl);
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

    private function errorResponse(string $message, int $status): JsonResponse|RedirectResponse
    {
        if (request()->expectsJson() || request()->header('Accept') === 'application/json') {
            return response()->json(['message' => $message], $status);
        }

        $frontendUrl = config('app.frontend_url', config('app.url'));
        return redirect($frontendUrl . '/verify-email?error=' . urlencode($message));
    }
}