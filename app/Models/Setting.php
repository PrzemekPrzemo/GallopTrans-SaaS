<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'key',
        'value',
        'type',
        'group',
        'label',
    ];
}
