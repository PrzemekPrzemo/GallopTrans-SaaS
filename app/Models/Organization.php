<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;

class Organization extends Model
{
    use Billable;
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'company_address',
        'company_nip',
        'company_phone',
        'company_email',
        'company_bank',
        'logo_path',
        'locale',
        'currency',
        'timezone',
        'plan',
        'trial_ends_at',
        'ksef_mode',
        'ksef_identifier',
        'ksef_cert_path',
        'ksef_token_encrypted',
        'invoice_number_format',
        'invoice_payment_due_days',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Organization $org) {
            if (empty($org->slug)) {
                $base = Str::slug($org->name ?: 'org');
                $slug = $base;
                $i = 2;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $i++;
                }
                $org->slug = $slug;
            }
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class);
    }

    public function onActivePlan(): bool
    {
        if ($this->trial_ends_at && $this->trial_ends_at->isFuture()) {
            return true;
        }
        return $this->subscribed('default');
    }
}
