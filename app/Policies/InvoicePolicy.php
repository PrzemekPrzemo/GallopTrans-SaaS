<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['owner', 'admin', 'operator'], true);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->role !== 'driver'
            && $invoice->organization_id === $user->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->canManage() || $user->role === 'operator';
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->canManage();
    }
}
