<?php

namespace App\Services\Notifications;

use App\Exceptions\UnnormalisableAddressException;

/**
 * Normalises a recipient address to the one form a provider will accept.
 *
 * The rule from the plan is "never guess a number", and that is stricter than it
 * sounds. A number that cannot be normalised with certainty is dropped and logged
 * rather than sent to a best guess, because a notification delivered to the wrong
 * number is a customer's data leaving the building.
 */
class AddressNormaliser
{
    /**
     * The only country we accept a local-format number for without being told the
     * country. Guessing here would be indistinguishable from inventing a number.
     */
    private const DEFAULT_COUNTRY_DIAL = '256';

    /**
     * E.164 allows 8 to 15 digits including country code. Outside that, a number is
     * either a typo or something that is not a phone number.
     */
    private const MIN_LENGTH = 8;

    private const MAX_LENGTH = 15;

    public function phone(mixed $raw): ?string
    {
        if (! is_string($raw) && ! is_numeric($raw)) {
            return null;
        }

        // Strip the formatting people actually type: spaces, dashes, dots, parens.
        $digits = preg_replace('/[^0-9+]/', '', trim((string) $raw)) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '+')) {
            return $this->e164(substr($digits, 1));
        }

        // 256XXXXXXXXX, i.e. the country code written without a plus.
        if (str_starts_with($digits, self::DEFAULT_COUNTRY_DIAL)
            && strlen($digits) === 12) {
            return $this->e164($digits);
        }

        // 07XXXXXXXX, the local form. 0 is the trunk prefix, not part of the number.
        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return $this->e164(self::DEFAULT_COUNTRY_DIAL.substr($digits, 1));
        }

        // 7XXXXXXXX, the local form with the trunk 0 already dropped.
        if (strlen($digits) === 9 && str_starts_with($digits, '7')) {
            return $this->e164(self::DEFAULT_COUNTRY_DIAL.$digits);
        }

        return null;
    }

    /**
     * @throws UnnormalisableAddressException
     */
    public function phoneOrFail(mixed $raw, string $context = ''): string
    {
        $phone = $this->phone($raw);

        if ($phone === null) {
            throw UnnormalisableAddressException::phone($raw, $context);
        }

        return $phone;
    }

    public function email(mixed $raw): ?string
    {
        if (! is_string($raw)) {
            return null;
        }

        $email = mb_strtolower(trim($raw));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $email;
    }

    /**
     * @throws UnnormalisableAddressException
     */
    public function emailOrFail(mixed $raw, string $context = ''): string
    {
        $email = $this->email($raw);

        if ($email === null) {
            throw UnnormalisableAddressException::email($raw, $context);
        }

        return $email;
    }

    private function e164(string $digits): ?string
    {
        $length = strlen($digits);

        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            return null;
        }

        return '+'.$digits;
    }
}
