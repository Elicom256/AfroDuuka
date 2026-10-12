<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Raised when a sale would take more units of a product than the branch holds.
 *
 * This replaces a bare `throw new Exception(..., 301)`. Two things were wrong with
 * that. 301 is "Moved Permanently", so it is not a meaningful status for a failed
 * checkout — and because the POS controller reads `$e->getCode()` as the HTTP
 * status and clamps anything outside 400–599, the real outcome was a 500: the till
 * reported a server fault for what is a conflict between the cart and the shelf, and
 * the till operator's natural response was to retry.
 *
 * Extending ConflictHttpException means Laravel renders it as a 409 with this
 * message, without any controller needing to know the type. The condition itself is
 * a business rule, not an HTTP concern, which is why the stock detail lives in
 * context() rather than in the status line.
 */
class InsufficientStockException extends ConflictHttpException
{
    public function __construct(
        public readonly int $productId,
        public readonly int $requested,
        public readonly int $available,
        ?string $productName = null,
    ) {
        parent::__construct($this->describe($productName, $requested, $available));
    }

    /**
     * Named constructor so the call site reads as the rule it is rather than as an
     * argument order nobody can check by eye.
     */
    public static function for(
        int $productId,
        int $requested,
        int $available,
        ?string $productName = null,
    ): self {
        return new self($productId, $requested, $available, $productName);
    }

    /**
     * Payload for the client so the till UI can show the shortfall without a second
     * request, and so a retry loop has something concrete to act on.
     */
    public function context(): array
    {
        return [
            'product_id' => $this->productId,
            'requested' => $this->requested,
            'available' => $this->available,
            'shortfall' => max(0, $this->requested - $this->available),
        ];
    }

    private static function describe(?string $name, int $requested, int $available): string
    {
        $subject = $name !== null && $name !== ''
            ? sprintf('%s (only %d available, %d requested)', $name, $available, $requested)
            : sprintf('only %d units available, %d requested', $available, $requested);

        return sprintf('Not enough stock to complete this sale: %s.', $subject);
    }
}
