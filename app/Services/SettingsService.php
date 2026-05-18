<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Tenant-scoped key-value store. Każda organizacja ma własny zestaw kluczy.
 * Cache per-organization żeby nie uderzać DB przy każdej kalkulacji.
 */
final class SettingsService
{
    public static function get(string $key, mixed $default = null): mixed
    {
        $orgId = self::orgId();
        if (! $orgId) {
            return $default;
        }

        $all = Cache::remember(self::cacheKey($orgId), 300, function () use ($orgId) {
            return Setting::query()
                ->withoutGlobalScopes()
                ->where('organization_id', $orgId)
                ->pluck('value', 'key')
                ->toArray();
        });

        if (! array_key_exists($key, $all)) {
            return $default;
        }

        $val = $all[$key];

        // Auto-cast wg domyślnej wartości (prosty heurystyk)
        if (is_bool($default)) {
            return in_array(strtolower((string) $val), ['1', 'true', 'yes', 'on'], true);
        }
        if (is_int($default)) {
            return (int) $val;
        }
        if (is_float($default)) {
            return (float) $val;
        }
        return $val ?? $default;
    }

    public static function set(string $key, mixed $value, string $type = 'string', string $group = 'general', ?string $label = null): void
    {
        $orgId = self::orgId();
        if (! $orgId) {
            throw new \RuntimeException('No tenant context for settings.');
        }

        Setting::withoutGlobalScopes()->updateOrCreate(
            ['organization_id' => $orgId, 'key' => $key],
            ['value' => is_scalar($value) ? (string) $value : json_encode($value), 'type' => $type, 'group' => $group, 'label' => $label],
        );

        Cache::forget(self::cacheKey($orgId));
    }

    /** @param array<string,mixed> $kv */
    public static function setMany(array $kv): void
    {
        foreach ($kv as $k => $v) {
            self::set($k, $v);
        }
    }

    private static function orgId(): ?int
    {
        if (app()->bound('tenant.id')) {
            return (int) app('tenant.id');
        }
        return auth()->user()?->organization_id;
    }

    private static function cacheKey(int $orgId): string
    {
        return "settings.org.{$orgId}";
    }
}
