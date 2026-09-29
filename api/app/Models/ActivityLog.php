<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Request;

class ActivityLog extends Activity
{
    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'batch_uuid',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public static function boot()
    {
        parent::boot();

        static::saving(function ($activity) {
            $activity->ip_address = Request::ip();
            $activity->user_agent = Request::userAgent();
        });
    }

    public function getChangesAttribute($value)
    {
        $properties = $this->properties;

        if (!$properties) {
            return null;
        }

        $changes = [];

        if (isset($properties['attributes'])) {
            $changes['after'] = $properties['attributes'];
        }

        if (isset($properties['old'])) {
            $changes['before'] = $properties['old'];
        }

        return $changes;
    }

    public function getSensitiveDataAttribute()
    {
        $sensitiveFields = ['password', 'token', 'secret', 'credit_card', 'cvv', 'pin'];
        $properties = $this->properties ?? collect();

        $masked = [];

        foreach ($sensitiveFields as $field) {
            if ($properties->has("attributes.$field")) {
                $masked["attributes.$field"] = '******';
            }
            if ($properties->has("old.$field")) {
                $masked["old.$field"] = '******';
            }
        }

        return $masked;
    }
}
