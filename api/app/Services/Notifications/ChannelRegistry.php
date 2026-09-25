<?php

namespace App\Services\Notifications;

use App\Contracts\Notifications\NotificationChannel;
use App\Notifications\Channels\LogMailChannel;
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
    /** @var array<string, class-string<NotificationChannel>> */
    private const DEFAULTS = [
        'whatsapp' => WhatsAppChannel::class,
        'email' => LogMailChannel::class,
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
