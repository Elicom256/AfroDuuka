<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppConfig;
use App\Models\WhatsAppTemplate;

/**
 * Mirrors Meta's approval status onto our template rows.
 *
 * template_status is the gate for dispatch: TemplateResolver refuses to send a
 * notification whose template is not APPROVED, so this class is the only thing that can
 * make a real-Meta business start sending. It therefore only ever moves a status that
 * Meta actually reported.
 *
 * Two rules the rest of the system depends on:
 *
 *   - A failed or empty read changes nothing. Meta returns [] both for "no templates"
 *     and for "could not reach Meta", and the two must not be conflated: treating the
 *     second as the first would revoke approval from templates that are perfectly
 *     approved, and the business would silently stop receiving messages.
 *   - Only a template that was actually matched is written. There is no "mark the rest
 *     rejected" pass, because a business may legitimately have templates registered
 *     here that are not part of this catalogue.
 *
 * body and variables are never touched. The owner edits those, and Meta does not send
 * them back to us anyway.
 */
class TemplateSyncService
{
    public function __construct(
        private readonly WhatsAppProviderFactory $providers,
    ) {}

    /**
     * @return array{business_id: int, fetched: int, approved: int, pending: int, rejected: int, unchanged: int, unmatched: int, ok: bool}
     */
    public function sync(WhatsAppConfig $config): array
    {
        $remote = $this->providers->for($config)->listTemplates();

        $result = [
            'business_id' => $config->business_id,
            'fetched' => count($remote),
            'approved' => 0,
            'pending' => 0,
            'rejected' => 0,
            'unchanged' => 0,
            'unmatched' => 0,
            // An empty read is reported as not-ok. A business with genuinely no
            // templates registered has not synced anything, and the command should say
            // so rather than report a clean sweep of zero.
            'ok' => $remote !== [],
        ];

        if ($remote === []) {
            return $result;
        }

        $index = $this->index($remote);

        $templates = WhatsAppTemplate::withoutGlobalScopes()
            ->where('business_id', $config->business_id)
            ->get();

        foreach ($templates as $template) {
            $match = $this->match($template, $index);

            if ($match === null) {
                $result['unmatched']++;

                continue;
            }

            $status = $this->normaliseStatus($match['status']);

            if ($status === null) {
                $result['unmatched']++;

                continue;
            }

            $result[strtolower($status)]++;

            $fill = ['last_synced_at' => now()];

            if ($template->template_status === $status) {
                $result['unchanged']++;
            } else {
                $fill['template_status'] = $status;
            }

            if (blank($template->provider_name)) {
                $fill['provider_name'] = $match['name'];
            }

            // Only overwrite what we already had a value for. A null here means the row
            // never recorded it, and writing the remote value would be fine, but
            // writing over a set value the owner curated is not.
            if (filled($template->template_category) && $match['category'] !== null) {
                $fill['template_category'] = $match['category'];
            }

            if (filled($template->parameter_format) && $match['parameter_format'] !== null) {
                $fill['parameter_format'] = $match['parameter_format'];
            }

            $template->forceFill($fill)->save();
        }

        return $result;
    }

    /**
     * @param  array<int, array<string, mixed>>  $remote
     * @return array<string, array<string, mixed>>
     */
    private function index(array $remote): array
    {
        $index = [];

        foreach ($remote as $template) {
            $index[$this->key($template['name'], $template['language'])] = $template;
        }

        return $index;
    }

    /**
     * Match on the name we provisioned as provider_name, falling back to the template
     * name for rows that predate provider_name being filled in.
     *
     * Two normalisations, both because our identifiers and Meta's are formatted
     * differently rather than because either is wrong: our types are dotted
     * (registration.welcome) and Meta's names are underscored (registration_welcome).
     * Matching on the dotted form would leave every template PENDING forever, with no
     * way for the owner to tell that apart from a template Meta actually rejected.
     *
     * Language is part of the match, not decoration. A business with en_UK and en_US
     * versions of one template is a normal Meta setup, and approving the wrong locale
     * is a real failure, so a name-only match is only accepted as a fallback when
     * nothing matches the language.
     *
     * @param  array<int, array<string, mixed>>  $index
     * @return array<string, mixed>|null
     */
    private function match(WhatsAppTemplate $template, array $index): ?array
    {
        $language = strtolower((string) ($template->language_code ?: $template->locale));
        $names = array_values(array_unique(array_filter([
            filled($template->provider_name) ? $template->provider_name : null,
            $template->name,
        ])));

        $byNameOnly = null;

        foreach ($names as $name) {
            $slug = $this->slug($name);

            foreach ($index as $entry) {
                if ($this->slug($entry['name']) !== $slug) {
                    continue;
                }

                if (strtolower((string) $entry['language']) === $language) {
                    return $entry;
                }

                // Hold the first locale mismatch in case nothing matches exactly.
                $byNameOnly ??= $entry;
            }
        }

        return $byNameOnly;
    }

    private function key(string $name, string $language): string
    {
        return strtolower($name).'|'.strtolower($language);
    }

    private function slug(string $value): string
    {
        return strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '_', $value));
    }

    /**
     * Meta's own vocabulary. Anything unrecognised becomes null and the template is
     * left alone, so a new status Meta introduces does not read as a rejection.
     */
    private function normaliseStatus(string $status): ?string
    {
        $status = strtoupper(trim($status));

        return match ($status) {
            'APPROVED' => 'APPROVED',
            'PENDING' => 'PENDING',
            'REJECTED', 'DISABLED', 'PAUSED', 'LIMITED' => 'REJECTED',
            default => null,
        };
    }
}
