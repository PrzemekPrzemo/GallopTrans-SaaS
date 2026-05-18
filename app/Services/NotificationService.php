<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;

/**
 * Tworzenie wewnętrznych notyfikacji (bell icon w UI).
 * Generuje rekord per-user (każdy odpowiedni członek org dostaje swoją kopię).
 */
final class NotificationService
{
    public static function notifyOrg(int $organizationId, array $roles, string $type, string $title, ?string $message = null, ?string $link = null): int
    {
        $users = User::query()->withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->whereIn('role', $roles)
            ->where('is_active', true)
            ->pluck('id');

        $now = now();
        $rows = $users->map(fn ($uid) => [
            'organization_id' => $organizationId,
            'user_id'         => $uid,
            'type'            => $type,
            'title'           => $title,
            'message'         => $message,
            'link'            => $link,
            'created_at'      => $now,
        ])->all();

        if ($rows) {
            AppNotification::withoutGlobalScopes()->insert($rows);
        }

        return count($rows);
    }

    public static function notifyUser(int $userId, int $organizationId, string $type, string $title, ?string $message = null, ?string $link = null): void
    {
        AppNotification::withoutGlobalScopes()->create([
            'organization_id' => $organizationId,
            'user_id'         => $userId,
            'type'            => $type,
            'title'           => $title,
            'message'         => $message,
            'link'            => $link,
            'created_at'      => now(),
        ]);
    }
}
