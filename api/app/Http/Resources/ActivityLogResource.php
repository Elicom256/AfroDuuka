<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\ActivityLog
 */
class ActivityLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'log_name' => $this->log_name,
            'event' => $this->event,
            'description' => $this->description,
            'subject' => [
                'type' => $this->subject_type ? class_basename($this->subject_type) : null,
                'id' => $this->subject_id,
                'label' => $this->subject_label,
            ],
            'causer' => [
                'type' => $this->causer_type ? class_basename($this->causer_type) : null,
                'id' => $this->causer_id,
                'name' => $this->display_name,
            ],
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'properties' => $this->properties,
            'changes' => $this->masked_changes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
