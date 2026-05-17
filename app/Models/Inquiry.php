<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    use BelongsToOrganization;

    protected $guarded = ['id'];

    protected $casts = [
        'transport_date' => 'date',
    ];
}
