<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class FuelPrice extends Model
{
    use BelongsToOrganization;

    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'fuel_type',
        'price_per_liter',
        'currency',
        'source',
        'valid_for_date',
        'fetched_at',
    ];

    protected $casts = [
        'price_per_liter' => 'float',
        'valid_for_date' => 'date',
        'fetched_at' => 'datetime',
    ];
}
