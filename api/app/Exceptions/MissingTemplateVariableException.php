<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A template could not be rendered into something safe to send.
 *
 * Thrown rather than logged-and-skipped because every route to this exception means
 * the message would have gone out with a literal {{placeholder}} in it, or with the
 * wrong value in the wrong position. The plan's rule is to fail loudly in
 * development and quietly in production, which the dispatcher implements by
 * catching this and recording a failed delivery.
 */
class MissingTemplateVariableException extends RuntimeException
{
    /**
     * @param  array<int, string>  $declared
     */
    public static function forVariable(string $name, array $declared): self
    {
        return new self(sprintf(
            'Template declares variable "%s" but no value was supplied. Declared: [%s].',
            $name,
            implode(', ', $declared)
        ));
    }

    /**
     * @param  array<int, string>  $declared
     */
    public static function undeclared(string $name, array $declared): self
    {
        return new self(sprintf(
            'Template body uses {{%s}} but the template does not declare it. Declared: [%s]. '
            .'A placeholder with no declared position cannot be mapped to Meta\'s {{1}}...{{n}}.',
            $name,
            $declared === [] ? 'none' : implode(', ', $declared)
        ));
    }

    public static function unrenderable(mixed $value): self
    {
        return new self(sprintf(
            'Template value of type %s cannot be rendered as a single line. '
            .'Flatten it before rendering rather than sending "Array" to a customer.',
            get_debug_type($value)
        ));
    }
}
