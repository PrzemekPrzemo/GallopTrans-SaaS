<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'invoice_id',
        'description',
        'qty',
        'unit',
        'unit_price_net',
        'total_net',
        'vat_percent',
        'sort_order',
    ];

    protected $casts = [
        'qty'            => 'float',
        'unit_price_net' => 'float',
        'total_net'      => 'float',
        'vat_percent'    => 'float',
    ];
}
