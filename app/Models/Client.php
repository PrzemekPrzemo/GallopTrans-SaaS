<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'name', 'email', 'phone', 'company', 'nip', 'address',
        'default_rate_per_km', 'default_min_amount', 'notes',
    ];

    protected $casts = [
        'default_rate_per_km' => 'float',
        'default_min_amount'  => 'float',
    ];

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function totalRevenue(): float
    {
        return (float) $this->quotes()->where('status', 'accepted')->sum('total_gross');
    }
}
