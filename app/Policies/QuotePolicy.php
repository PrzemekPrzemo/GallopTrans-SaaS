<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Quote;
use App\Models\User;

/**
 * Driver może zobaczyć tylko ofertę przypisaną do siebie (driver_id lub created_by).
 * Owner/admin/operator mają pełny dostęp w ramach swojej organizacji.
 */
class QuotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Quote $quote): bool
    {
        if ($user->role === 'driver') {
            return $quote->driver_id === $user->id || $quote->created_by === $user->id;
        }
        return $quote->organization_id === $user->organization_id;
    }

    public function update(User $user, Quote $quote): bool
    {
        return $user->canManage() || ($user->role === 'operator');
    }

    public function delete(User $user, Quote $quote): bool
    {
        return $user->canManage();
    }
}
