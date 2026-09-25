<?php

namespace App\Services\Notifications;

use App\Contracts\Notifications\NotificationChannel;
use App\Notifications\Channels\LogMailChannel;
use App\Notifications\Channels\SesMailChannel;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Contracts\Container\Container;

/**
 * Resolves a channel name to its transport.
 *
 * A registry rather than a match statement, so adding a channel does not mean editing
 * the dispatcher and the job. Resolved from the container, which is what lets a
 * transport depend on config and the database without the dispatcher knowing.
 */
class ChannelRegistry
{
    /**
     * Transport per channel name.
     *
     * email resolves to SesMailChannel, which hands the message to whatever
     * MAIL_MAILER is configured — so `log` and `array` still work locally and in tests
     * with no SES credentials at all. LogMailChannel stays registered and reachable by
     * name for the case where a test wants to assert that nothing was actually sent.
     */
    /** @var array<string, class-string<NotificationChannel>> */
    private const DEFAULTS = [
        'whatsapp' => WhatsAppChannel::class,
        'email' => SesMailChannel::class,
    ];

    /** @var array<string, NotificationChannel> */
    private array $resolved = [];

    public function __construct(
        private readonly Container $container,
        /** @var array<string, class-string<NotificationChannel>> */
        private readonly array $channels = self::DEFAULTS,
    ) {}

    public function for(string $name): ?NotificationChannel
    {
        if (isset($this->resolved[$name])) {
            return $this->resolved[$name];
        }

        $class = $this->channels[$name] ?? null;

        if ($class === null) {
            return null;
        }

        return $this->resolved[$name] = $this->container->make($class);
    }

    /**
     * @return array<int, string>
     */
    public function available(): array
    {
        return array_keys($this->channels);
    }
}
