<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Ses\SesEventApplier;
use App\Services\Ses\SnsSignatureVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Receives SES event publishing from SNS.
 *
 * Unauthenticated by necessity: SNS has no shared secret with us, so the request is
 * authenticated by the RSA signature over the body. That check happens before the body
 * is interpreted at all, because this endpoint can deactivate a customer's notifications
 * and nothing about who is calling it is known until the signature verifies.
 *
 * Always answers 200 for a verified message, including for events it does not act on.
 * Anything else makes SNS retry the whole notification, so a message about a delivery we
 * have never heard of — or one about a mail sent before the Message-ID scheme existed —
 * would be redelivered indefinitely.
 */
class SesWebhookController extends Controller
{
    /**
     * Reject a forged signature with 403 so SNS stops retrying it. A body SNS genuinely
     * sent but we failed to parse is a 200, so it is not retried either.
     */
    public function __invoke(
        Request $request,
        SnsSignatureVerifier $verifier,
        SesEventApplier $applier,
    ): JsonResponse {
        $payload = $request->all();

        if (! $verifier->verify($payload)) {
            Log::warning('Rejected an unverified SES webhook', [
                'topic' => $payload['TopicArn'] ?? null,
                'type' => $payload['Type'] ?? null,
            ]);

            return response()->json(['message' => 'Invalid signature.'], 403);
        }

        $type = (string) ($payload['Type'] ?? '');

        if ($type === 'SubscriptionConfirmation') {
            $this->confirmSubscription($payload);

            return response()->json(['message' => 'Subscription confirmed.']);
        }

        if ($type !== 'NotificationMessage') {
            return response()->json(['message' => 'Ignored.']);
        }

        $events = $this->eventsFrom($payload);

        if ($events === null) {
            // Verified, so genuinely from SNS, but not a shape we understand. Retrying
            // will not make it parseable.
            Log::warning('SES webhook body was not parseable', [
                'message_id' => $payload['MessageId'] ?? null,
            ]);

            return response()->json(['message' => 'Unparseable body.']);
        }

        $applied = 0;

        foreach ($events as $event) {
            if ($applier->apply($event) !== null) {
                $applied++;
            }
        }

        return response()->json(['applied' => $applied]);
    }

    /**
     * SNS confirms a topic subscription by asking us to visit a SubscribeURL. Skipping
     * this is the single most common reason a webhook appears dead: the endpoint is
     * never called again, because the subscription was never established.
     *
     * The URL is only followed after the signature has already verified, so it is
     * genuinely AWS's and not a forged redirect.
     *
     * @param  array<string, mixed>  $payload
     */
    private function confirmSubscription(array $payload): void
    {
        $url = (string) ($payload['SubscribeURL'] ?? '');

        if ($url === '') {
            return;
        }

        try {
            Http::timeout(10)->get($url);
        } catch (Throwable $e) {
            Log::warning('Could not confirm the SES topic subscription', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>|null
     */
    private function eventsFrom(array $payload): ?array
    {
        $message = json_decode((string) ($payload['Message'] ?? ''), true);

        if (! is_array($message)) {
            return null;
        }

        $events = $message['notificationType'] !== null && array_is_list($message)
            ? $message
            : [$message];

        return array_values(array_filter($events, 'is_array'));
    }
}
