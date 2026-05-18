<?php

declare(strict_types=1);

namespace App\Observers;

use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;

/**
 * Generyczny observer logujący CRUD do audit_log. Podłącz do modeli
 * krytycznych biznesowo (Quote, Invoice, Payment, Vehicle, Setting).
 */
class AuditObserver
{
    public function created(Model $model): void
    {
        AuditService::log(
            class_basename($model) . '.created',
            class_basename($model),
            (int) $model->getKey(),
            $this->changedFields($model->getAttributes()),
        );
    }

    public function updated(Model $model): void
    {
        // Zapisujemy tylko realnie zmienione pola.
        $changes = $model->getChanges();
        unset($changes['updated_at']);
        if (! $changes) {
            return;
        }
        AuditService::log(
            class_basename($model) . '.updated',
            class_basename($model),
            (int) $model->getKey(),
            $changes,
        );
    }

    public function deleted(Model $model): void
    {
        AuditService::log(
            class_basename($model) . '.deleted',
            class_basename($model),
            (int) $model->getKey(),
        );
    }

    /** @param array<string,mixed> $attrs */
    private function changedFields(array $attrs): array
    {
        // Wyrzuć pola wrażliwe / niepotrzebne w logu.
        return collect($attrs)
            ->except(['password', 'remember_token', 'ksef_token_encrypted', 'updated_at', 'created_at'])
            ->toArray();
    }
}
