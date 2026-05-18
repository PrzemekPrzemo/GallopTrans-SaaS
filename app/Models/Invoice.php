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
        'advance_invoice_ids' => 'array',
        'settled_from_advances' => 'float',
        'issued_at'           => 'date',
        'sold_at'             => 'date',
        'payment_due_at'      => 'date',
        'last_reminder_at'    => 'datetime',
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

    public function correctedBy(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Invoice::class, 'corrects_invoice_id');
    }

    public function correctsInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'corrects_invoice_id');
    }
}
