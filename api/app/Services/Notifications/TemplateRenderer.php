<?php

namespace App\Services\Notifications;

use App\Exceptions\MissingTemplateVariableException;
use App\ValueObjects\RenderedTemplate;

/**
 * Renders our own template wording into a body plus an ordered parameter list.
 *
 * Substitution is driven by the template's declared `variables`, never by whatever
 * keys happen to be in the data array. That ordering is the contract with Meta: a
 * template approved as {{1}} {{2}} needs its values in exactly that order, and
 * deriving the order from a loosely built associative array makes the mapping
 * accidental. Worse, the previous implementation fed the `variables` column in as
 * *values* (B6), so `{{product_name}}` rendered as the literal text "product_name".
 *
 * Both failure directions throw. A body that still contains {{...}} after rendering,
 * or a declared variable with no value, means the message would reach a customer with
 * a placeholder in it — which is worse than not sending, because it looks delivered.
 */
class TemplateRenderer
{
    private const PLACEHOLDER = '/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/';

    /**
     * @param  array<int, string>  $variableNames  The template's declared variable
     *                                             names, in positional order.
     * @param  array<string, mixed>  $values
     *
     * @throws MissingTemplateVariableException
     */
    public function render(string $body, array $variableNames, array $values = []): RenderedTemplate
    {
        $parameters = [];
        $rendered = $body;

        foreach (array_values($variableNames) as $name) {
            $name = (string) $name;

            if (! array_key_exists($name, $values)) {
                throw MissingTemplateVariableException::forVariable($name, $variableNames);
            }

            $value = $this->stringify($values[$name]);

            $parameters[$name] = $value;
            $rendered = str_replace('{{'.$name.'}}', $value, $rendered);
        }

        // A placeholder the template never declared would otherwise survive into the
        // outgoing message untouched.
        if (preg_match(self::PLACEHOLDER, $rendered, $matches)) {
            throw MissingTemplateVariableException::undeclared($matches[1], $variableNames);
        }

        return new RenderedTemplate($rendered, $parameters);
    }

    /**
     * The values in positional order, for a Meta template approved as {{1}}…{{n}}.
     *
     * @param  array<int, string>  $variableNames
     * @param  array<string, mixed>  $values
     * @return array<int, string>
     */
    public function orderedValues(array $variableNames, array $values = []): array
    {
        $rendered = $this->render('', $variableNames, $values);

        return array_values($rendered->parameters);
    }

    /**
     * Flatten a value to the single line Meta will accept.
     *
     * A newline inside a template parameter is rejected by the API, and money or dates
     * arrive as Carbon instances and arrays often enough that quietly rendering
     * "Array" into a customer-facing message is not acceptable.
     */
    private function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            throw MissingTemplateVariableException::unrenderable($value);
        }

        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('j M Y');
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            $value = (string) $value;
        }

        if (! is_scalar($value)) {
            throw MissingTemplateVariableException::unrenderable($value);
        }

        return trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '');
    }
}
