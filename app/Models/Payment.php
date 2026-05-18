<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use BelongsToOrganization;

    protected $guarded = ['id'];

    protected $casts = [
        'amount_gross' => 'float',
        'amount_net' => 'float',
        'vat_amount' => 'float',
        'paid_at' => 'date',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}
