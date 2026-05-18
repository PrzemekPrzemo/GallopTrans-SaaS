<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;

/**
 * Prosty logger akcji użytkownika - zapisuje do tabeli audit_log.
 * Wywoływany ręcznie w kluczowych miejscach (Quote::created, Invoice::created itp.)
 * lub przez observery na modelach.
 */
final class AuditService
{
    /** @param array<string,mixed>|null $payload */
    public static function log(string $action, ?string $entity = null, ?int $entityId = null, ?array $payload = null): void
    {
        $orgId = auth()->user()->organization_id ?? (app()->bound('tenant.id') ? app('tenant.id') : null);
        if (! $orgId) {
            return;
        }

        AuditLog::withoutGlobalScopes()->create([
            'organization_id' => $orgId,
            'user_id'   => auth()->id(),
            'action'    => $action,
            'entity'    => $entity,
            'entity_id' => $entityId,
            'payload'   => $payload,
            'ip'        => request()?->ip(),
            'created_at'=> now(),
        ]);
    }
}
