<?php

namespace App\Services\WhatsApp;

class WhatsAppTemplateService
{
    public function render(string $template, array $data = []): string
    {
        $rendered = $template;

        foreach ($data as $key => $value) {
            $rendered = str_replace('{{' . $key . '}}', (string) $value, $rendered);
            $rendered = str_replace('{' . $key . '}', (string) $value, $rendered);
        }

        return $rendered;
    }
}
