<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Spatie\Activitylog\Models\Activity;

/**
 * @property int $id
 * @property string|null $log_name
 * @property string|null $description
 * @property string|null $event
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property string|null $causer_type
 * @property int|null $causer_id
 * @property \Illuminate\Support\Collection|null $properties
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property int|null $business_id
 * @property int|null $business_branch_id
 * @property \Illuminate\Support\Collection $changes
 */
class ActivityLog extends Activity
{
    use SoftDeletes;

    protected $fillable = [
        'log_name',
        'description',
        'event',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'batch_uuid',
        'ip_address',
        'user_agent',
        'business_id',
        'business_branch_id',
    ];

    /**
     * Spatie reads `properties` as a Collection in changes() and getExtraProperty(),
     * so it must stay cast to a collection rather than a plain array.
     */
    protected function casts(): array
    {
        return [
            'properties' => 'collection',
            'subject_id' => 'integer',
            'causer_id' => 'integer',
            'business_id' => 'integer',
            'business_branch_id' => 'integer',
        ];
    }

    /**
     * Stamps request and tenancy context on write without clobbering values the
     * caller already provided explicitly (seeders, queued jobs, CLI).
     */
    protected static function booted(): void
    {
        static::creating(function (self $activity) {
            $activity->ip_address ??= Request::ip();
            $activity->user_agent ??= Request::userAgent();

            $user = Auth::user();

            if ($user) {
                $activity->causer_type ??= $user->getMorphClass();
                $activity->causer_id ??= $user->getKey();
                $activity->business_id ??= $user->business_id;
                $activity->business_branch_id ??= $user->business_branch_id;
            }
        });
    }

    public function scopeForBusiness(Builder $query, ?int $businessId): Builder
    {
        return $query->when($businessId, fn (Builder $q) => $q->where('business_id', $businessId));
    }

    /**
     * Scopes to a single user. The morph type is included because causer_id alone
     * is not unique across the polymorphic causer models.
     */
    public function scopeCausedByUser(Builder $query, Model $user): Builder
    {
        return $query
            ->where('causer_type', $user->getMorphClass())
            ->where('causer_id', $user->getKey());
    }

    /**
     * Accepts a comma separated list and ignores the "all" sentinel used by the UI.
     */
    public function scopeInLogNames(Builder $query, ?string $logNames): Builder
    {
        $names = collect(explode(',', (string) $logNames))
            ->map(fn (string $name) => trim($name))
            ->filter(fn (string $name) => $name !== '' && strtolower($name) !== 'all')
            ->unique()
            ->values();

        return $query->when($names->isNotEmpty(), fn (Builder $q) => $q->whereIn('log_name', $names->all()));
    }

    public function getDisplayNameAttribute(): ?string
    {
        return $this->causer?->name;
    }

    public function getSubjectLabelAttribute(): ?string
    {
        if (! $this->subject_type || ! $this->subject_id) {
            return null;
        }

        return class_basename($this->subject_type).' #'.$this->subject_id;
    }

    /**
     * Before/after pairs with sensitive values masked.
     */
    public function getMaskedChangesAttribute(): array
    {
        $sensitive = ['password', 'password_confirmation', 'token', 'secret', 'credit_card', 'cvv', 'pin'];

        $mask = function (?array $values) use ($sensitive): array {
            foreach ($sensitive as $field) {
                if (array_key_exists($field, $values)) {
                    $values[$field] = '******';
                }
            }

            return $values;
        };

        // Must call changes() explicitly: $this->changes would resolve to Eloquent's
        // protected dirty-tracking property from inside the class scope, not the accessor.
        $changes = $this->changes();

        return [
            'before' => $mask($changes->get('old', [])),
            'after' => $mask($changes->get('attributes', [])),
        ];    }
}
