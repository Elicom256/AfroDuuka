<?php

namespace App\Services\WhatsApp;

use App\Contracts\Notifications\ChannelResult;

/**
 * The one place that reads what a provider said.
 *
 * A provider answers with a plain array, and the implementations disagree about its
 * shape. The demo provider always reports `success`. Meta never does: it reports a
 * refusal by returning an `error` key, and on success it returns the message id under
 * a third key name again. Nothing in the interface said any of that, so every caller
 * guessed.
 *
 * Guessing is what this exists to end. `ProcessWhatsAppNotificationJob` read
 * `$result['success']`, a key the demo provider always sets and Meta never sets, so
 * every genuine Meta failure raised an undefined-key warning and fell through to
 * 'failed' — correct by accident, and one rename away from filing refusals as
 * successes. The identical bug in the monthly report job was fixed on that job alone
 * while the live sender kept it, which is what a shared reader is for.
 *
 * Interpreting a response is also not the same as knowing whether a message arrived,
 * and the difference is why this returns a three-outcome type. A timeout, or a 2xx
 * whose body could not be parsed, means the provider may have taken the message
 * anyway. Recording those as failures is how one uncertain send becomes two.
 */
final class ProviderResponse
{
    /**
     * Codes where the request may or may not have landed.
     *
     * A timeout tells us nothing about whether it was delivered, so it is ambiguous
     * rather than a failure. `unreadable_response` belongs here for the same reason —
     * Meta answered 2xx, so it probably took the message, but the reply could not be
     * parsed and there is no id to reconcile on.
     *
     * @var array<int, string>
     */
    private const AMBIGUOUS = [
        'timeout',
        'curl_timeout',
        'connection_error',
        'unreadable_response',
    ];

    /**
     * @param  array<string, mixed>  $response
     */
    public static function interpret(array $response): ChannelResult
    {
        $error = $response['error'] ?? null;

        if ($error !== null) {
            $code = (string) ($response['code'] ?? 'provider_error');
            $message = is_array($error) ? json_encode($error) : (string) $error;

            // The provider's own code is carried through, so the record says which kind
            // of doubt this was rather than a flat "ambiguous" for all of them: a 5xx, a
            // dead connection and an unparseable reply are three different operational
            // problems that collapse into one value the moment they are recorded.
            return in_array($code, self::AMBIGUOUS, true)
                ? ChannelResult::ambiguous($message, $response, $code)
                : ChannelResult::rejected($code, $message, $response);
        }

        // Three shapes, because the providers disagree and the disagreement was silent.
        // Meta returns `messages[0].id`; the demo provider returns `provider_message_id`.
        // Reading only the first two accepted every send with a null message id, so the
        // delivery was marked sent with nothing to correlate against — and the status
        // webhook, whose whole job is to match a delivery to Meta's message id, would
        // have matched nothing at all. No test noticed, because nothing asserted the id
        // was stored.
        $messageId = $response['message_id']
            ?? $response['messages'][0]['id']
            ?? $response['provider_message_id']
            ?? null;

        return ChannelResult::accepted($messageId === null ? null : (string) $messageId, $response);
    }

    private function __construct() {}
}
