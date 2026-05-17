<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'name',
        'plate',
        'fuel_type',
        'fuel_consumption',
        'horse_capacity',
        'max_weight_kg',
        'height_m',
        'length_m',
        'width_m',
        'axles',
        'is_trailer',
        'is_default',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'fuel_consumption' => 'float',
        'height_m' => 'float',
        'length_m' => 'float',
        'width_m' => 'float',
        'is_trailer' => 'boolean',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];
}
