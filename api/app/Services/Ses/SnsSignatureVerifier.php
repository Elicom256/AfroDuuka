<?php

namespace App\Services\Ses;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Verifies that an SNS POST really came from AWS.
 *
 * This endpoint is unauthenticated by necessity — SNS signs the body instead — and it
 * can mark an email address as undeliverable and deactivate a customer's notifications.
 * Without signature verification, anyone who learns the URL could unsubscribe a
 * competitor's customers, or suppress a business's billing mail, by posting forged
 * bounce events. So every field the signature covers is checked before any of the
 * payload is read for meaning.
 *
 * The public key is fetched from the SigningCertURL in the message, which makes the
 * verifier a server-side request forgery primitive unless that URL is constrained. It
 * is: HTTPS only, and the host must be an amazonaws.com domain. An attacker cannot host
 * a certificate at their own domain and have it accepted.
 */
class SnsSignatureVerifier
{
    /**
     * Only ever fetch a signing certificate from AWS.
     */
    private const CERT_HOST_SUFFIX = '.amazonaws.com';

    /**
     * @param  array<string, mixed>  $payload  The decoded SNS envelope.
     */
    public function verify(array $payload): bool
    {
        $signature = (string) ($payload['Signature'] ?? '');
        $version = (string) ($payload['SignatureVersion'] ?? '');

        if ($signature === '' || $version === '') {
            return false;
        }

        $certificate = $this->certificate($payload);

        if ($certificate === null) {
            return false;
        }

        $publicKey = $this->publicKey($certificate);

        if ($publicKey === null) {
            return false;
        }

        $toSign = $this->stringToSign($payload);

        if ($toSign === null) {
            return false;
        }

        $algorithm = $version === '2' ? OPENSSL_ALGO_SHA256 : OPENSSL_ALGO_SHA1;

        $decoded = base64_decode($signature, true);

        if ($decoded === false) {
            return false;
        }

        return openssl_verify($toSign, $decoded, $publicKey, $algorithm) === 1;
    }

    /**
     * The exact byte sequence the signature covers, per the AWS documented layout.
     *
     * Field order is fixed by the spec and the fields are joined with newlines. Subject
     * is included only when present, which is the one part of the layout that varies by
     * message type, and Subscription/Unsubscribe confirmation use a different field set
     * from notifications entirely. Getting this wrong yields a verification failure, not
     * a bypass, so it fails closed.
     *
     * @param  array<string, mixed>  $payload
     */
    private function stringToSign(array $payload): ?string
    {
        $type = (string) ($payload['Type'] ?? '');

        $fields = match ($type) {
            'NotificationMessage' => ['Message', 'MessageId', 'Subject', 'Timestamp', 'TopicArn', 'Type'],
            'SubscriptionConfirmation', 'UnsubscribeConfirmation' => [
                'Message', 'MessageId', 'SubscribeURL', 'Timestamp', 'Token', 'TopicArn', 'Type',
            ],
            default => null,
        };

        if ($fields === null) {
            return null;
        }

        $lines = [];

        foreach ($fields as $field) {
            // An absent optional field is omitted, along with its own label line.
            if (! array_key_exists($field, $payload)) {
                if (in_array($field, ['Subject', 'SubscribeURL', 'Token'], true)) {
                    continue;
                }

                return null;
            }

            $lines[] = $field;
            $lines[] = (string) $payload[$field];
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function certificate(array $payload): ?string
    {
        $url = (string) ($payload['SigningCertURL'] ?? '');

        if (! $this->isAwsCertificateUrl($url)) {
            Log::warning('SNS message had a non-AWS SigningCertURL', [
                'url' => $url,
            ]);

            return null;
        }

        return Cache::rememberForever('sns:cert:'.sha1($url), function () use ($url) {
            try {
                $response = Http::timeout(10)->get($url);
            } catch (Throwable $e) {
                Log::warning('Could not fetch SNS signing certificate', ['error' => $e->getMessage()]);

                return null;
            }

            return $response->successful() ? $response->body() : null;
        });
    }

    /**
     * HTTPS, and genuinely on an AWS domain.
     *
     * Checks the parsed host rather than a substring, so
     * "https://sns.us-east-1.amazonaws.com.evil.test/cert.pem" — which contains the
     * allowed string — is rejected.
     */
    public function isAwsCertificateUrl(string $url): bool
    {
        $parts = parse_url($url);

        if ($parts === false || ($parts['scheme'] ?? null) !== 'https') {
            return false;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($host === '' || ! str_ends_with($host, self::CERT_HOST_SUFFIX)) {
            return false;
        }

        // A bare "amazonaws.com" is not a host AWS serves certificates from.
        return $host !== 'amazonaws.com' && ! preg_match('/^\d+\.\d+\.\d+\.\d+$/', $host);
    }

    /**
     * @return \OpenSSLAsymmetricKey|null Null when the certificate is not a usable key.
     */
    private function publicKey(string $certificate): ?\OpenSSLAsymmetricKey
    {
        $key = openssl_pkey_get_public($certificate);

        return $key === false ? null : $key;
    }
}
