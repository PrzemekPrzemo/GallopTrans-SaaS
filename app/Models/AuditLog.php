<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use BelongsToOrganization;

    protected $table = 'audit_log';

    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'user_id',
        'action',
        'entity',
        'entity_id',
        'payload',
        'ip',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];
}
