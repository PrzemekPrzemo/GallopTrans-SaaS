<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use BelongsToOrganization;

    protected $guarded = ['id'];

    protected $casts = [
        'subtotal_net'        => 'float',
        'vat_amount'          => 'float',
        'total_gross'         => 'float',
        'vat_percent'         => 'float',
        'ksef_response'       => 'array',
        'issued_at'           => 'date',
        'sold_at'             => 'date',
        'payment_due_at'      => 'date',
        'ksef_sent_at'        => 'datetime',
        'ksef_confirmed_at'   => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}
