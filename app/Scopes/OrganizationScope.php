<?php

declare(strict_types=1);

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope automatycznie filtrujący zapytania po organization_id
 * zalogowanego użytkownika. Dzięki temu nigdy nie wyciekną dane między tenantami.
 *
 * Aby wykonać zapytanie cross-tenant (np. seedy, superadmin, joby w kolejce):
 *   Model::withoutGlobalScope(OrganizationScope::class)->...
 */
final class OrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $orgId = app()->bound('tenant.id') ? app('tenant.id') : null;

        if ($orgId === null && auth()->check()) {
            $orgId = auth()->user()->organization_id ?? null;
        }

        if ($orgId !== null) {
            $builder->where($model->getTable() . '.organization_id', $orgId);
        }
    }
}
