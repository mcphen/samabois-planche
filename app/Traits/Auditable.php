<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Enregistre dans audit_logs chaque création, modification et suppression
 * du modèle : qui, quand, depuis quelle page, et les valeurs avant / après.
 */
trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(fn (Model $model) => $model->writeAudit('created', [], $model->auditableAttributes($model->getAttributes())));

        static::updated(function (Model $model) {
            $changes = $model->auditableAttributes($model->getChanges());

            if (empty($changes)) {
                return;
            }

            $old = array_intersect_key($model->auditableAttributes($model->getOriginal()), $changes);
            $model->writeAudit('updated', $old, $changes);
        });

        static::deleted(fn (Model $model) => $model->writeAudit('deleted', $model->auditableAttributes($model->getOriginal()), []));

        if (method_exists(static::class, 'restored')) {
            static::restored(fn (Model $model) => $model->writeAudit('restored', [], $model->auditableAttributes($model->getAttributes())));
        }
    }

    /**
     * Libellé lisible de l'élément dans le journal. Surchargeable par modèle.
     */
    public function auditLabel(): ?string
    {
        foreach (['numero_bl', 'numero', 'name', 'code', 'intitule'] as $attribute) {
            if (! empty($this->getAttribute($attribute))) {
                return (string) $this->getAttribute($attribute);
            }
        }

        return null;
    }

    protected function auditableAttributes(array $attributes): array
    {
        $ignored = array_merge(
            ['created_at', 'updated_at', 'remember_token', 'password', 'last_synced_at'],
            $this->getHidden()
        );

        return array_diff_key($attributes, array_flip($ignored));
    }

    protected function writeAudit(string $event, array $old, array $new): void
    {
        try {
            $user    = auth()->user();
            $request = app()->runningInConsole() ? null : request();

            AuditLog::create([
                'user_id'         => $user?->id,
                'user_name'       => $user?->name,
                'user_role'       => $user?->role,
                'event'           => $event,
                'auditable_type'  => static::class,
                'auditable_id'    => $this->getKey(),
                'auditable_label' => mb_substr((string) $this->auditLabel(), 0, 255) ?: null,
                'old_values'      => $old ?: null,
                'new_values'      => $new ?: null,
                'ip'              => $request?->ip(),
                'url'             => $request?->fullUrl(),
                'route_name'      => $request?->route()?->getName(),
            ]);
        } catch (Throwable $e) {
            // Le journal ne doit jamais bloquer l'opération métier
            Log::warning('Audit log failed: ' . $e->getMessage());
        }
    }
}
