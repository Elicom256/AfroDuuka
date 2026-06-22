<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'iso_alpha2',
        'flag_emoji',
        'currency_code',
        'currency_symbol',
        'default_vat_rate',
        'region',
        'calling_code',
    ];

    protected function casts(): array
    {
        return [
            'default_vat_rate' => 'decimal:2',
        ];
    }

    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }
}
