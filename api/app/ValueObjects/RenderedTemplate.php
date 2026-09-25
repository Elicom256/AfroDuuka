<?php

namespace App\ValueObjects;

/**
 * A rendered template: the customer-facing text, plus the values that produced it.
 *
 * The parameters are kept alongside the body because the ordered values are what
 * Meta actually receives, and because notification_deliveries.payload stores them so
 * a send can be replayed or debugged without re-rendering from source data that may
 * since have changed.
 */
final class RenderedTemplate
{
    /**
     * @param  string  $body  Fully substituted, safe to send.
     * @param  array<string, string>  $parameters  Declared variable name => rendered
     *                                             value, in declaration order.
     */
    public function __construct(
        public readonly string $body,
        public readonly array $parameters,
    ) {}

    /**
     * @return array<int, string>
     */
    public function orderedValues(): array
    {
        return array_values($this->parameters);
    }
}
