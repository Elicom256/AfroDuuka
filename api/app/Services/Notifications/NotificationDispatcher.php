<?php

namespace App\Services\Notifications;

use App\Exceptions\MissingTemplateVariableException;
use App\Exceptions\UnapprovedTemplateException;
use App\Exceptions\UnnormalisableAddressException;
use App\Jobs\SendNotificationJob;
use App\Models\NotificationDelivery;
use App\Models\NotificationRecipient;
use App\Models\WhatsAppConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The only entry point business modules use to send a notification.
 *
 * A business module calls dispatch() and knows nothing about templates, recipients,
 * preferences, dedupe or channels. That is the invariant worth protecting: the moment
 * a service starts building a message body or looking up a phone number, the rules
 * below start being reimplemented, slightly differently, per call site.
 *
 * Dedupe is reserve-then-send. The row is inserted with status=pending and a
 * deterministic key *before* anything is sent, and the unique index on that key is the
 * dedupe mechanism. A duplicate loses the insert race and returns quietly. Checking for
 * an existing row first and then sending is not equivalent: two listeners for the same
 * event both find nothing and both send.
 */
class NotificationDispatcher
{
    public function __construct(
        private readonly NotificationCatalogue $catalogue,
        private readonly RecipientResolver $recipients,
        private readonly PreferenceResolver $preferences,
        private readonly TemplateResolver $templates,
        private readonly AddressNormaliser $normaliser,
    ) {}

    /**
     * @param  string  $type  A key from config('notifications.catalogue').
     * @param  array<string, mixed>  $identity  Values for the catalogue's dedupe shape.
     * @param  array<string, mixed>  $values  Values for the template's {{token}}s.
     * @param  array<int, string>  $channels  Override the catalogue's channels. Useful
     *                                        for a caller that genuinely needs one
     *                                        transport, e.g. a customer receipt.
     */
    public function dispatch(
        string $type,
        int $businessId,
        array $identity = [],
        array $values = [],
        ?int $businessBranchId = null,
        array $channels = [],
        ?string $explicitAddress = null,
    ): DispatchResult {
        if (! $this->catalogue->has($type)) {
            // A typo in a notification type would otherwise silently do nothing.
            throw new \InvalidArgumentException(
                "Unknown notification type \"{$type}\". Nothing was sent."
            );
        }

        $channels = $channels === [] ? $this->catalogue->channelsFor($type) : $channels;
        $mandatory = $this->catalogue->isMandatory($type);
        $category = $this->catalogue->categoryFor($type);

        // A branch-scoped notification without a branch would go to the whole business.
        if ($this->catalogue->isBranchScoped($type) && $businessBranchId === null && $explicitAddress === null) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" is branch scoped and was dispatched with no business_branch_id. '
                .'Refusing to fan a branch alert out to the whole business.',
                $type
            ));
        }

        $result = new DispatchResult($type);

        foreach ($channels as $channel) {
            $this->dispatchToChannel(
                $type,
                $channel,
                $businessId,
                $businessBranchId,
                $identity,
                $values,
                $mandatory,
                $category,
                $explicitAddress,
                $result,
            );
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $identity
     * @param  array<string, mixed>  $values
     */
    private function dispatchToChannel(
        string $type,
        string $channel,
        int $businessId,
        ?int $businessBranchId,
        array $identity,
        array $values,
        bool $mandatory,
        string $category,
        ?string $explicitAddress,
        DispatchResult $result,
    ): void {
        $address = $explicitAddress !== null
            ? $this->recipients->explicitAddress($channel, $explicitAddress)
            : $this->recipients->resolveAddress($businessId, $channel, $businessBranchId, [
                'type' => $type,
            ]);

        if ($address === null) {
            // Nothing to send to. Recorded rather than thrown, because a business with
            // no WhatsApp number configured is a normal state, not an error.
            $this->suppress(
                $type, $channel, $businessId, $businessBranchId, $identity, $mandatory,
                NotificationDelivery::REASON_NO_RECIPIENT
            );

            $result->suppressed(NotificationDelivery::REASON_NO_RECIPIENT);

            return;
        }

        $recipient = $this->storedRecipientFor($businessId, $channel, $businessBranchId, $address);

        if (! $mandatory) {
            $check = $this->preferences->check($type, $channel, $address, $recipient);

            if (! $check['allowed']) {
                $this->suppress(
                    $type, $channel, $businessId, $businessBranchId, $identity, $mandatory,
                    $check['reason'] ?? NotificationDelivery::REASON_OPTED_OUT
                );

                $result->suppressed($check['reason'] ?? NotificationDelivery::REASON_OPTED_OUT);

                return;
            }
        }

        $template = null;

        if ($channel === NotificationRecipient::CHANNEL_WHATSAPP) {
            try {
                $rendered = $this->templates->forWhatsApp(
                    $businessId,
                    $type,
                    $values,
                    $this->whatsAppConfig($businessId)
                );

                $template = $this->templates->resolve($businessId, $type);
                $payload = $rendered->parameters;
                $body = $rendered->body;
            } catch (UnapprovedTemplateException|MissingTemplateVariableException|UnnormalisableAddressException $e) {
                $reason = $e instanceof UnapprovedTemplateException
                    ? NotificationDelivery::REASON_TEMPLATE_NOT_APPROVED
                    : NotificationDelivery::REASON_BAD_ADDRESS;

                $this->suppress(
                    $type, $channel, $businessId, $businessBranchId, $identity, $mandatory, $reason
                );

                $result->suppressed($reason);

                // Loud in development, quiet in production: a template problem is a
                // deploy-time bug, but a customer should never see a stack trace.
                if (app()->environment('local', 'testing')) {
                    report($e);
                } else {
                    Log::warning('Notification suppressed', [
                        'type' => $type,
                        'channel' => $channel,
                        'business_id' => $businessId,
                        'reason' => $reason,
                        'detail' => $e->getMessage(),
                    ]);
                }

                return;
            }
        } else {
            $body = null;
            // Email renders in Stage 2, in a Mailable. Storing the values here is what
            // lets the delivery row be replayed without re-deriving them.
            $payload = $values;
        }

        $delivery = $this->reserve(
            $type, $channel, $businessId, $businessBranchId, $identity, $address,
            $recipient, $mandatory, $category, $payload, $template, $body
        );

        if ($delivery === null) {
            // Lost the insert race: another handler already reserved this exact event.
            $result->duplicate();

            return;
        }

        $this->handOff($delivery, $channel);

        $result->reserved($delivery);
    }

    /**
     * Insert the pending row. Returns null when the key was already taken.
     *
     * Uses INSERT ... ON CONFLICT DO NOTHING rather than catching a unique violation,
     * which matters more than it looks. In Postgres a constraint violation aborts the
     * whole transaction, not just the statement, so catching the exception and carrying
     * on still leaves every later query in that transaction failing with 25P02. When the
     * dispatcher runs inside a business transaction — a listener firing mid-request, or
     * an afterCommit hook — the duplicate would have taken down the business operation
     * that triggered the notification. ON CONFLICT does not abort anything.
     *
     * @param  array<string, mixed>  $identity
     * @param  array<string, mixed>  $payload
     */
    private function reserve(
        string $type,
        string $channel,
        int $businessId,
        ?int $businessBranchId,
        array $identity,
        string $address,
        ?NotificationRecipient $recipient,
        bool $mandatory,
        string $category,
        array $payload,
        $template,
        ?string $body,
    ): ?NotificationDelivery {
        $key = $this->catalogue->dedupeKey($type, $identity + [
            'business_id' => $businessId,
            'business_branch_id' => $businessBranchId,
            'channel' => $channel,
        ]);

        return $this->insertOnce([
            'business_id' => $businessId,
            'business_branch_id' => $businessBranchId,
            'channel' => $channel,
            'category' => $category,
            'type' => $type,
            'template_key' => $template?->name ?? $type,
            'recipient_id' => $recipient?->id,
            'recipient_address' => $address,
            'status' => NotificationDelivery::STATUS_PENDING,
            'payload' => $payload,
            'dedupe_key' => $key,
            'meta_template_name' => $template?->provider_name,
            'meta_template_language' => $template?->language_code,
            'is_mandatory' => $mandatory,
        ]);
    }

    /**
     * Insert, or return null if this exact event was already reserved.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function insertOnce(array $attributes): ?NotificationDelivery
    {
        $now = now();

        // Json columns need encoding by hand on a builder insert; the model cast does
        // not run for a raw insert.
        $payload = $attributes['payload'] ?? null;

        $inserted = DB::table('notification_deliveries')->insertOrIgnore([
            ...$attributes,
            'payload' => $payload === null ? null : json_encode($payload),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($inserted === 0) {
            return null;
        }

        $id = DB::table('notification_deliveries')
            ->where('dedupe_key', $attributes['dedupe_key'])
            ->value('id');

        return $id === null
            ? null
            : NotificationDelivery::withoutGlobalScopes()->find($id);
    }

    /**
     * @param  array<string, mixed>  $identity
     */
    private function suppress(
        string $type,
        string $channel,
        int $businessId,
        ?int $businessBranchId,
        array $identity,
        bool $mandatory,
        string $reason,
    ): void {
        $this->insertOnce([
            'business_id' => $businessId,
            'business_branch_id' => $businessBranchId,
            'channel' => $channel,
            'category' => $this->catalogue->categoryFor($type),
            'type' => $type,
            'template_key' => $type,
            'status' => NotificationDelivery::STATUS_SUPPRESSED,
            'suppressed_reason' => $reason,
            'suppressed_at' => now(),
            // Suffixed so a suppression never collides with a real send of the same
            // event: "no recipient" and "recipient refused" are different outcomes and
            // both deserve a row.
            'dedupe_key' => $this->catalogue->dedupeKey($type, $identity + [
                'business_id' => $businessId,
                'business_branch_id' => $businessBranchId,
                'channel' => $channel,
            ]).':suppressed:'.$reason,
            'is_mandatory' => $mandatory,
        ]);
    }

    /**
     * Queue the transport. The job is what flips pending -> sending and calls the
     * channel; the dispatcher never sends inline, so a slow provider cannot hold up the
     * request that triggered the notification.
     */
    private function handOff(NotificationDelivery $delivery, string $channel): void
    {
        SendNotificationJob::dispatch($delivery->id, $channel);
    }

    /**
     * The stored recipient behind an address, so its per-category preferences can be
     * applied. Must follow the same branch-then-business chain as RecipientResolver,
     * or a branch notification would resolve the address via a business-level recipient
     * and then fail to find it, silently skipping that recipient's opt-outs.
     */
    private function storedRecipientFor(
        int $businessId,
        string $channel,
        ?int $businessBranchId,
        string $address,
    ): ?NotificationRecipient {
        $scopes = $businessBranchId !== null
            ? [$businessBranchId, null]
            : [null];

        foreach ($scopes as $scope) {
            $recipient = NotificationRecipient::withoutGlobalScopes()
                ->where('business_id', $businessId)
                ->where('channel', $channel)
                ->where('address', $address)
                ->when(
                    $scope !== null,
                    fn ($query) => $query->where('business_branch_id', $scope),
                    fn ($query) => $query->whereNull('business_branch_id')
                )
                ->first();

            if ($recipient !== null) {
                return $recipient;
            }
        }

        return null;
    }

    private function whatsAppConfig(int $businessId): ?WhatsAppConfig
    {
        return WhatsAppConfig::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->first();
    }
}
