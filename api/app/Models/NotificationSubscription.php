<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Email unsubscribe state for preference-checked mail.
 *
 * Deliberately not tenant-scoped: an address can be a recipient for more than one
 * business, and the unsubscribe has to apply to the address, not to a tenant.
 */
class NotificationSubscription extends Model
{
    protected $fillable = [
        'email',
        'token',
        'categories',
        'unsubscribed_at',
        'resubscribed_at',
    ];

    protected function casts(): array
    {
        return [
            'categories' => 'array',
            'unsubscribed_at' => 'datetime',
            'resubscribed_at' => 'datetime',
        ];
    }

    /**
     * Look up by address and create the row on first use.
     *
     * createOrFirst rather than firstOrCreate: two requests for the same brand new
     * address can both miss the initial SELECT, and the loser's INSERT would fail
     * on the email unique index. createOrFirst swallows that collision and re-selects.
     *
     * The token stays null here. It is issued on the first email that actually needs
     * a List-Unsubscribe link, and never shared between rows.
     */
    public static function forEmail(string $email): self
    {
        $email = mb_strtolower(trim($email));

        return static::createOrFirst(
            ['email' => $email],
            ['categories' => []]
        );
    }

    /**
     * Issue a fresh unsubscribe token and return it in plaintext.
     *
     * Only the hash is stored, so the raw value cannot be recovered from the row
     * afterwards. Each email that carries a List-Unsubscribe header therefore rotates
     * the token, which is also what invalidates a previously leaked link.
     */
    public function issueToken(): string
    {
        $raw = Str::random(64);

        $this->forceFill(['token' => hash('sha256', $raw)])->save();

        return $raw;
    }

    /**
     * Resolve the subscription behind a token taken from an unsubscribe URL.
     */
    public static function findByToken(?string $raw): ?self
    {
        if (blank($raw)) {
            return null;
        }

        return static::where('token', hash('sha256', $raw))->first();
    }

    public function isUnsubscribed(): bool
    {
        return $this->unsubscribed_at !== null;
    }

    public function isSubscribedTo(string $category): bool
    {
        if ($this->isUnsubscribed()) {
            return false;
        }

        $categories = $this->categories ?? [];

        return $categories === [] || in_array($category, $categories, true);
    }

    public function unsubscribe(): void
    {
        $this->forceFill(['unsubscribed_at' => now(), 'resubscribed_at' => null])->save();
    }

    public function resubscribe(): void
    {
        $this->forceFill(['unsubscribed_at' => null, 'resubscribed_at' => now()])->save();
    }
}
