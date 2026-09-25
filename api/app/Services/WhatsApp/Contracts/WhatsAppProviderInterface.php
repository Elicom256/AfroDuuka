<?php

namespace App\Services\WhatsApp\Contracts;

interface WhatsAppProviderInterface
{
    public function getName(): string;

    public function validateConfiguration(array $config): bool;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function sendMessage(array $payload): array;

    /**
     * The template templates currently registered on the provider, normalised to
     * name/language/status/category/parameter-format.
     *
     * Returns an empty array when the provider cannot answer. Approval status decides
     * whether a notification may be dispatched at all, so this is deliberately
     * best-effort: a provider that cannot be reached must leave stored statuses alone
     * rather than guess, because guessing APPROVED would send real messages against an
     * unapproved template and guessing the other way silently stops delivery.
     *
     * @return array<int, array{name: string, language: string, status: string, category: ?string, parameter_format: ?string}>
     */
    public function listTemplates(): array;
}
