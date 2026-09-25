<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A WhatsApp notification cannot be rendered into something Meta will accept.
 *
 * Two distinct causes, kept apart because the fix is different for each: the template
 * row was never provisioned, or Meta has not approved it yet. The second is routine —
 * every new template is PENDING until Meta reviews it — and the first means a
 * provisioning step was skipped.
 */
class UnapprovedTemplateException extends RuntimeException
{
    public static function for(string $type, ?string $providerName, ?string $status): self
    {
        return new self(sprintf(
            'Template for "%s" is not approved by Meta (provider_name: %s, template_status: %s). '
            .'Suppressing rather than sending: an unapproved send is rejected at the API and '
            .'is how a business loses its quality score. Run duukaflow:whatsapp:sync-templates.',
            $type,
            $providerName ?? 'null',
            $status ?? 'null'
        ));
    }

    public static function missing(int $businessId, string $type): self
    {
        return new self(sprintf(
            'No WhatsAppTemplate named "%s" for business %d. Templates resolve on an exact '
            .'name match against the notification type, so run the WhatsAppTemplateSeeder.',
            $type,
            $businessId
        ));
    }
}
