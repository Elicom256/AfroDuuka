<?php

namespace App\Services\WhatsApp;

use App\Services\Notifications\TemplateRenderer;

/**
 * Kept only so the two existing jobs (ProcessWhatsAppNotificationJob,
 * GenerateMonthlyBusinessReportJob) still have somewhere to call render().
 *
 * New code should use App\Services\Notifications\TemplateRenderer directly. This
 * wrapper cannot do the strict work the renderer does, because it is handed a data
 * array with no declared variable order, so it renders best-effort and leaves an
 * unresolved {{placeholder}} in place rather than throwing mid-send. That is exactly
 * the behaviour the renderer exists to replace, so migrate those call sites before
 * relying on this for anything customer-facing.
 */
class WhatsAppTemplateService
{
    public function __construct(
        private readonly TemplateRenderer $renderer = new TemplateRenderer,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function render(string $template, array $data = []): string
    {
        $rendered = $template;

        foreach ($data as $key => $value) {
            $value = is_scalar($value) || $value === null ? (string) $value : '';
            $rendered = str_replace('{{'.$key.'}}', $value, $rendered);
            $rendered = str_replace('{'.$key.'}', $value, $rendered);
        }

        return $rendered;
    }

    /**
     * Strict render. Throws rather than shipping an unresolved placeholder.
     *
     * @param  array<int, string>  $variableNames
     * @param  array<string, mixed>  $data
     */
    public function renderStrict(string $template, array $variableNames, array $data = []): string
    {
        return $this->renderer->render($template, $variableNames, $data)->body;
    }
}
