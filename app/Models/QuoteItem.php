<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuoteItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'quote_id',
        'item_type',
        'description',
        'qty',
        'unit',
        'unit_price_net',
        'total_net',
        'sort_order',
    ];

    protected $casts = [
        'qty' => 'float',
        'unit_price_net' => 'float',
        'total_net' => 'float',
    ];
}
