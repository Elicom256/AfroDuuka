<?php

namespace App\Services\Notifications;

use App\Exceptions\MissingTemplateVariableException;
use App\Exceptions\UnapprovedTemplateException;
use App\Models\WhatsAppConfig;
use App\Models\WhatsAppTemplate;
use App\ValueObjects\RenderedTemplate;

/**
 * Resolves and renders the template for one notification.
 *
 * Approval is enforced here, once, for both channels' worth of concerns: a WhatsApp
 * send whose template Meta has not approved is refused outright, because Meta rejects
 * it at the API and an unapproved send is also how a business loses its quality score.
 *
 * The demo provider is the one exception. There is no Meta account behind a demo
 * business, so requiring an approval that can never arrive would mean demo mode sends
 * nothing at all — which is how the previous implementation ended up with templates
 * that looked configured and were never used.
 */
class TemplateResolver
{
    public function __construct(
        private readonly NotificationCatalogue $catalogue,
        private readonly TemplateRenderer $renderer,
    ) {}

    /**
     * @param  array<string, mixed>  $values
     *
     * @throws UnapprovedTemplateException
     * @throws MissingTemplateVariableException
     */
    public function forWhatsApp(
        int $businessId,
        string $type,
        array $values,
        ?WhatsAppConfig $config = null,
    ): RenderedTemplate {
        $template = $this->resolve($businessId, $type);

        if ($this->requiresApproval($config) && ! $template->isApproved()) {
            throw UnapprovedTemplateException::for($type, $template->provider_name, $template->template_status);
        }

        return $this->renderer->render($template->body, $template->variables ?? [], $values);
    }

    /**
     * Exact name match, and only that.
     *
     * A suffix or category match would be a bug waiting to happen: subscription.created,
     * order.purchase.created and order.sale.created all end in "created", and
     * inventory.low_stock would match a low_stock_alert written for something else
     * entirely. Failing loudly on a missing template is the correct outcome, because the
     * alternative is sending the owner someone else's words.
     */
    public function resolve(int $businessId, string $type): WhatsAppTemplate
    {
        $template = WhatsAppTemplate::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->where('name', $type)
            ->orderBy('id')
            ->first();

        if ($template === null) {
            throw UnapprovedTemplateException::missing($businessId, $type);
        }

        return $template;
    }

    /**
     * A missing template is reported as its own case rather than folded into
     * "not approved", because the remedy is different: provision it, versus wait for
     * Meta.
     */
    private function requiresApproval(?WhatsAppConfig $config): bool
    {
        $provider = $config?->provider ?? 'demo';

        return $provider !== 'demo';
    }
}
