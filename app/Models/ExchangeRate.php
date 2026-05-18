<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'code',
        'rate',
        'source',
        'valid_for_date',
        'fetched_at',
    ];

    protected $casts = [
        'rate' => 'float',
        'valid_for_date' => 'date',
        'fetched_at' => 'datetime',
    ];
}
