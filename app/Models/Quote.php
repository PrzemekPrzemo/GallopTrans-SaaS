<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quote extends Model
{
    use BelongsToOrganization;

    protected $guarded = ['id'];

    protected $casts = [
        'waypoints' => 'array',
        'distance_km' => 'float',
        'return_distance_km' => 'float',
        'fuel_consumption' => 'float',
        'fuel_price' => 'float',
        'base_rate_per_km' => 'float',
        'surcharge_percent' => 'float',
        'extra_horse_fee' => 'float',
        'difficult_horse_fee' => 'float',
        'fixed_fees' => 'float',
        'toll_cost' => 'float',
        'min_quote_amount' => 'float',
        'stay_24h_cost' => 'float',
        'exchange_rate' => 'float',
        'vat_percent' => 'float',
        'subtotal_net' => 'float',
        'vat_amount' => 'float',
        'total_gross' => 'float',
        'round_trip' => 'boolean',
        'transport_date' => 'date',
        'valid_until' => 'date',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
