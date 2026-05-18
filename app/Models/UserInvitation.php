<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UserInvitation extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'email',
        'role',
        'token',
        'invited_by',
        'expires_at',
        'accepted_at',
    ];

    protected $casts = [
        'expires_at'  => 'datetime',
        'accepted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (UserInvitation $i) {
            if (empty($i->token)) {
                $i->token = Str::random(64);
            }
            if (empty($i->expires_at)) {
                $i->expires_at = now()->addDays(7);
            }
        });
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return ! $this->accepted_at && ! $this->isExpired();
    }
}
