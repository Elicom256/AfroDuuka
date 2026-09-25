<?php

namespace App\Contracts\Notifications;

/**
 * The outcome of one handoff to a provider.
 *
 * Three outcomes, not two, because "we do not know" has to be distinguishable from
 * "it failed". A timeout after the request left the building may still have been
 * delivered; retrying it is how a customer gets the same message twice.
 */
final class ChannelResult
{
    private function __construct(
        public readonly bool $accepted,
        public readonly bool $ambiguous,
        public readonly ?string $providerMessageId,
        public readonly ?string $errorCode,
        public readonly ?string $errorMessage,
        public readonly array $raw,
    ) {}

    /**
     * The provider took the message. Delivery confirmation still comes from the webhook.
     */
    public static function accepted(?string $providerMessageId = null, array $raw = []): self
    {
        return new self(true, false, $providerMessageId, null, null, $raw);
    }

    /**
     * The provider definitively refused it. Safe to retry, and the reason is worth
     * keeping: a 4xx here is a bug in our payload, not a transient fault.
     */
    public static function rejected(string $errorCode, string $errorMessage, array $raw = []): self
    {
        return new self(false, false, null, $errorCode, $errorMessage, $raw);
    }

    /**
     * The request may or may not have landed. Leave the row in `sending` and let the
     * status webhook decide; do not retry on this alone.
     */
    public static function ambiguous(string $errorMessage, array $raw = []): self
    {
        return new self(false, true, null, 'ambiguous', $errorMessage, $raw);
    }

    public function isSuccessful(): bool
    {
        return $this->accepted;
    }
}
