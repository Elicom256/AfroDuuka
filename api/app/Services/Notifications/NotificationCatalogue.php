<?php

namespace App\Services\Notifications;

use InvalidArgumentException;

/**
 * Typed read access to config('notifications.catalogue'), with the invariants
 * checked in one place.
 *
 * A malformed catalogue entry is a programming error, not a runtime condition: it
 * would mean a notification type resolving to no template, or a branch-scoped
 * notification fanning out to the whole business. Both are silent, dangerous and
 * only visible in production, so they are caught the first time the catalogue is
 * read rather than the first time a customer is affected.
 */
class NotificationCatalogue
{
    /** @var array<string, array<string, mixed>>|null */
    private ?array $validated = null;

    /**
     * @return array<string, mixed>
     */
    public function get(string $type): array
    {
        $this->validate();

        $entry = $this->validated()[$type] ?? null;

        if ($entry === null) {
            throw new InvalidArgumentException(sprintf(
                'Unknown notification type "%s". Known types: %s.',
                $type,
                implode(', ', array_keys($this->validated()))
            ));
        }

        return $entry;
    }

    public function has(string $type): bool
    {
        $this->validate();

        return isset($this->validated()[$type]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        $this->validate();

        return $this->validated();
    }

    /**
     * The categories that cannot be opted out of.
     *
     * Derived from the catalogue rather than hardcoded, because the two drift: the
     * brief names three categories while the catalogue marks a different set, and a
     * hardcoded list would quietly disagree with the per-entry `mandatory` flags that
     * the dispatcher actually enforces. Deriving it means the seed a new recipient
     * starts with can never be a category set the dispatcher would then refuse to send.
     *
     * @return array<int, string>
     */
    public function mandatoryCategories(): array
    {
        $categories = [];

        foreach ($this->all() as $entry) {
            if (! empty($entry['mandatory'])) {
                $categories[$entry['category']] = true;
            }
        }

        $names = array_keys($categories);
        sort($names);

        return $names;
    }

    /**
     * Whether a category is non-suppressible, read off the same per-entry flags the
     * dispatcher uses.
     *
     * This exists so that "is this mandatory?" has exactly one answer in the codebase.
     * It previously also consulted a hardcoded transactional list in config, and the
     * two disagreed for the system and order categories — which meant a mandatory
     * notification could be suppressed as opted-out by the preference check that was
     * supposed to be the thing that never suppresses it.
     */
    public function isMandatoryCategory(string $category): bool
    {
        return in_array($category, $this->mandatoryCategories(), true);
    }

    /**
     * Categories that are preference-checked, i.e. the ones a recipient can opt out of.
     *
     * Needed whenever an opt-out has to be written as an explicit allow-list: an empty
     * `categories` column means "everything is allowed", so recording "unsubscribed from
     * reports" against a row that is still empty is not a state the column can express.
     * Materialising the full set of optional categories and removing one from it is.
     *
     * @return array<int, string>
     */
    public function optionalCategories(): array
    {
        $categories = [];

        foreach ($this->all() as $entry) {
            $category = $entry['category'];

            if (! $this->isMandatoryCategory($category)) {
                $categories[$category] = true;
            }
        }

        $names = array_keys($categories);
        sort($names);

        return $names;
    }

    /**
     * @return array<int, string>
     */
    public function channelsFor(string $type): array
    {
        return $this->get($type)['channels'];
    }

    public function isMandatory(string $type): bool
    {
        return $this->get($type)['mandatory'];
    }

    public function categoryFor(string $type): string
    {
        return $this->get($type)['category'];
    }

    /**
     * branch notifications are addressed to one branch and must never fall back to
     * the whole business, so the caller has to be able to ask.
     */
    public function isBranchScoped(string $type): bool
    {
        return $this->get($type)['scope'] === 'branch';
    }

    /**
     * The Meta template a WhatsApp send needs approved, or null when the
     * notification is email-only.
     */
    public function metaTemplateFor(string $type): ?string
    {
        return $this->get($type)['meta'];
    }

    /**
     * Attachment builder names this type's email carries.
     *
     * Declared per entry rather than inferred from the type, for the same reason
     * channels are: a notification that gains a PDF is then a config change, and one
     * that loses it cannot keep building the file on every send.
     *
     * @return array<int, string>
     */
    public function attachmentsFor(string $type): array
    {
        return $this->get($type)['attachments'] ?? [];
    }

    /**
     * Build the deterministic dedupe key for one occurrence of a notification.
     *
     * The shapes are declared here rather than at each call site so that two
     * listeners firing for the same event cannot disagree about the key and send
     * twice. Every placeholder must be supplied: an unresolved {placeholder} left in
     * the key would collapse distinct events into one, silently swallowing the
     * second notification.
     *
     * @param  array<string, mixed>  $identity
     */
    public function dedupeKey(string $type, array $identity): string
    {
        $shape = $this->get($type)['dedupe'];

        $missing = [];

        $key = preg_replace_callback(
            '/\{([a-z0-9_]+)\}/i',
            function (array $matches) use ($identity, &$missing): string {
                $name = $matches[1];

                if (! array_key_exists($name, $identity) || blank($identity[$name])) {
                    $missing[] = $name;

                    return $matches[0];
                }

                return (string) $identity[$name];
            },
            $shape
        ) ?? $shape;

        if ($missing !== []) {
            throw new InvalidArgumentException(sprintf(
                'Dedupe key for "%s" is missing identity: %s. Shape is "%s".',
                $type,
                implode(', ', array_unique($missing)),
                $shape
            ));
        }

        return $key;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function validated(): array
    {
        if ($this->validated === null) {
            $this->validate();
        }

        return $this->validated;
    }

    private function validate(): void
    {
        if ($this->validated !== null) {
            return;
        }

        $catalogue = config('notifications.catalogue', []);
        $categories = config('notifications.categories', []);

        if (! is_array($catalogue) || $catalogue === []) {
            throw new InvalidArgumentException('config/notifications.php has no catalogue entries.');
        }

        foreach ($catalogue as $type => $entry) {
            foreach (['label', 'category', 'channels', 'mandatory', 'scope', 'dedupe'] as $key) {
                if (! array_key_exists($key, $entry)) {
                    throw new InvalidArgumentException(
                        "Catalogue entry \"{$type}\" is missing \"{$key}\"."
                    );
                }
            }

            if (! in_array($entry['category'], $categories, true)) {
                throw new InvalidArgumentException(sprintf(
                    'Catalogue entry "%s" uses category "%s", which is not in notifications.categories.',
                    $type,
                    $entry['category']
                ));
            }

            if ($entry['channels'] === []) {
                throw new InvalidArgumentException("Catalogue entry \"{$type}\" has no channels.");
            }

            foreach ($entry['channels'] as $channel) {
                if (! in_array($channel, ['email', 'whatsapp'], true)) {
                    throw new InvalidArgumentException(sprintf(
                        'Catalogue entry "%s" names unknown channel "%s".',
                        $type,
                        $channel
                    ));
                }
            }

            if (! in_array($entry['scope'], ['business', 'branch'], true)) {
                throw new InvalidArgumentException(sprintf(
                    'Catalogue entry "%s" has unknown scope "%s".',
                    $type,
                    $entry['scope']
                ));
            }

            // A WhatsApp send cannot be approved without a Meta template name, and an
            // email-only notification has no reason to carry one. Catching the mix-up
            // here means a template-not-approved suppression is never a surprise.
            if (in_array('whatsapp', $entry['channels'], true) && empty($entry['meta'])) {
                throw new InvalidArgumentException(sprintf(
                    'Catalogue entry "%s" sends WhatsApp but declares no meta template.',
                    $type
                ));
            }

            if (! in_array('whatsapp', $entry['channels'], true) && ! empty($entry['meta'])) {
                throw new InvalidArgumentException(sprintf(
                    'Catalogue entry "%s" declares meta template "%s" but never sends WhatsApp.',
                    $type,
                    $entry['meta']
                ));
            }

            // Same reasoning as the meta checks above, inverted: an attachment on a
            // notification with no email channel is dead config. It would be built on
            // nothing, or — worse, once a WhatsApp-only type grows an email channel
            // years from now — quietly start attaching to a notification nobody
            // reviewed it for.
            if (isset($entry['attachments'])) {
                if (! in_array('email', $entry['channels'], true)) {
                    throw new InvalidArgumentException(sprintf(
                        'Catalogue entry "%s" declares attachments but never sends email.',
                        $type
                    ));
                }

                if (! is_array($entry['attachments']) || $entry['attachments'] === []) {
                    throw new InvalidArgumentException(sprintf(
                        'Catalogue entry "%s" has an empty attachments list. Omit the key instead.',
                        $type
                    ));
                }

                foreach ($entry['attachments'] as $name) {
                    if (! is_string($name) || trim($name) === '') {
                        throw new InvalidArgumentException(sprintf(
                            'Catalogue entry "%s" has a non-string attachment name.',
                            $type
                        ));
                    }
                }
            }

            $this->validated[$type] = $entry;
        }
    }
}
