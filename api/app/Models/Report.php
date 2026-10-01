<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * A scheduled report definition (name, type, cadence) for one business.
 *
 * Extends BaseModel so the business global scope applies — as a plain Model it was
 * readable across every tenant. business_id stays in $fillable because ReportSeeder
 * assigns it explicitly while running with no authenticated user to fall back on.
 */
class Report extends BaseModel
{
    /** @use HasFactory<\Database\Factories\ReportFactory> */
    use HasFactory;

    protected $fillable = [
        'business_id',
        'name',
        'type',
        'description',
        'parameters',
        'schedule',
        'is_active',
    ];

    protected $casts = [
        'parameters' => 'array',
        'is_active' => 'boolean',
    ];
}
