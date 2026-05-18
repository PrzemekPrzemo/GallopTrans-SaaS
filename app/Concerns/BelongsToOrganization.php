<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\Organization;
use App\Scopes\OrganizationScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait dla modeli przypisanych do tenanta.
 *
 * - automatycznie filtruje SELECT przez OrganizationScope (auth user → jego org)
 * - automatycznie wypełnia organization_id przy zapisie
 */
trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope(new OrganizationScope());

        static::creating(function ($model) {
            if (empty($model->organization_id)) {
                $orgId = app()->bound('tenant.id')
                    ? app('tenant.id')
                    : (auth()->user()->organization_id ?? null);
                if ($orgId) {
                    $model->organization_id = $orgId;
                }
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
