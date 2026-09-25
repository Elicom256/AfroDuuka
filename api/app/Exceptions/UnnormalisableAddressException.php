<?php

namespace App\Exceptions;

use InvalidArgumentException;

/**
 * An address could not be normalised to a form a provider will accept.
 *
 * Separate from MissingTemplateVariableException because the handling differs: a
 * rendering failure is a bug in our payload and should be loud, whereas a bad address
 * is usually bad data in the business's own record, and the send should be skipped
 * and logged rather than retried forever.
 */
class UnnormalisableAddressException extends InvalidArgumentException
{
    public static function phone(mixed $raw, string $context = ''): self
    {
        return new self(sprintf(
            'Cannot normalise %s to E.164%s. Refusing to guess: a notification sent to the '
            .'wrong number is a customer\'s data leaving the building.',
            self::describe($raw),
            $context === '' ? '' : " ({$context})"
        ));
    }

    public static function email(mixed $raw, string $context = ''): self
    {
        return new self(sprintf(
            'Cannot normalise %s to an email address%s.',
            self::describe($raw),
            $context === '' ? '' : " ({$context})"
        ));
    }

    private static function describe(mixed $raw): string
    {
        $type = get_debug_type($raw);

        if (is_string($raw)) {
            $shown = mb_substr($raw, 0, 32);

            return $type.' "'.$shown.'"';
        }

        return $type;
    }
}
