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
}
