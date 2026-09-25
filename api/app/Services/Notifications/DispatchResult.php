<?php

namespace App\Services\Notifications;

use App\Models\NotificationDelivery;

/**
 * What one dispatch() call actually did.
 *
 * Returned rather than logged so callers can assert on it. "Nothing was sent" is a
 * normal outcome — a duplicate, a business with no number, an opted-out recipient — and
 * a caller that needs to know which of those happened should not have to go read the
 * database to find out.
 */
final class DispatchResult
{
    /** @var array<int, NotificationDelivery> */
    private array $reserved = [];

    /** @var array<int, string> */
    private array $suppressed = [];

    private int $duplicates = 0;

    public function __construct(public readonly string $type) {}

    public function reserved(NotificationDelivery $delivery): void
    {
        $this->reserved[] = $delivery;
    }

    public function suppressed(string $reason): void
    {
        $this->suppressed[] = $reason;
    }

    public function duplicate(): void
    {
        $this->duplicates++;
    }

    /**
     * @return array<int, NotificationDelivery>
     */
    public function deliveries(): array
    {
        return $this->reserved;
    }

    /**
     * @return array<int, string>
     */
    public function suppressionReasons(): array
    {
        return $this->suppressed;
    }

    public function duplicates(): int
    {
        return $this->duplicates;
    }

    public function wasSent(): bool
    {
        return $this->reserved !== [];
    }

    public function nothingHappened(): bool
    {
        return $this->reserved === [] && $this->suppressed === [] && $this->duplicates === 0;
    }
}
