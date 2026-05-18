<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::creating(function (User $u) {
            if (empty($u->calendar_token)) {
                $u->calendar_token = Str::random(40);
            }
        });
    }

    protected $fillable = [
        'organization_id',
        'name',
        'email',
        'password',
        'role',
        'phone',
        'locale',
        'calendar_token',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function canManage(): bool
    {
        return in_array($this->role, ['owner', 'admin'], true);
    }
}
