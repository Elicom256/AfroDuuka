<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Services\Mail\BusinessVerificationMailService;
use App\Support\FrontendUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

        // Browser client: redirect to the frontend verification page. It was
        // /onboarding?verified=1, a screen that only exists once and is not where a
        // person clicking a link from their inbox belongs — and the route the SPA
        // actually renders for this is /verify-email.
        return redirect(FrontendUrl::path('verify-email').'?verified=1');
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

        // resend() returns null when the per-business cooldown has not elapsed. The
        // response is identical either way on purpose: a message that changes with the
        // cooldown lets a caller time when the last mail was sent, and there is nothing
        // to gain by telling a legitimate user "wait 40 seconds" that the client's own
        // countdown does not already cover.
        //
        // A mail problem must never surface as a 500 or a debug trace. The owner asked
        // for a link and the link is recoverable, so a delivery failure — the transport
        // refusing, a bad credential, a down SMTP — is logged and answered in plain words
        // instead. The signup path relies on the same guarantee.
        try {
            $this->verificationService->resend($business);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'We could not send the verification email right now. Please try again in a moment.',
            ], 502);
        }

        return response()->json([
            'message' => 'Verification email sent',
        ], 200);
    }

    private function errorResponse(string $message, int $status): JsonResponse|RedirectResponse
    {
        if (request()->expectsJson() || request()->header('Accept') === 'application/json') {
            return response()->json(['message' => $message], $status);
        }

        $frontendUrl = FrontendUrl::path('verify-email');

        return redirect($frontendUrl.'?error='.urlencode($message));
    }
}
