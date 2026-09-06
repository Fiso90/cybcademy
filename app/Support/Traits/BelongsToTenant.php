<?php

declare(strict_types=1);

namespace App\Support\Traits;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;

/**
 * Applies automatic tenant scoping to any Eloquent model with a
 * `tenant_id` column.
 *
 * This is the application-layer half of the two-layer tenant isolation
 * design confirmed in Phase 4 Section 4 / Phase 7 Section 2 (A01 mitigation):
 * PostgreSQL Row-Level Security is the database-level backstop, and this
 * global scope is the first line of defence, ensuring a developer who
 * forgets an explicit ->where('tenant_id', ...) clause still cannot
 * accidentally query across tenants.
 *
 * Applied via `use BelongsToTenant;` on every tenant-scoped model
 * (see app/Modules/*\/Models). New models MUST use this trait unless there
 * is a documented, reviewed reason not to (Phase 5 Section 6's
 * "opt-out, not opt-in" principle applies equally at this layer).
 */
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            $tenantId = TenantContext::current();

            if ($tenantId !== null) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', $tenantId);
            }
        });

        static::creating(function ($model): void {
            if (empty($model->tenant_id)) {
                $model->tenant_id = TenantContext::current();
            }
        });
    }

    /**
     * Escape hatch for genuinely cross-tenant operations (e.g. a Solunar
     * internal support tool, or a scheduled job that legitimately needs to
     * iterate all tenants). Every call site using this MUST be logged via
     * the audit_logs break-glass convention (Phase 7 Section 7) - this
     * method intentionally does not do that logging itself, to keep the
     * responsibility visible at the call site rather than hidden here.
     */
    public static function withoutTenantScope(): Builder
    {
        return static::withoutGlobalScope('tenant');
    }
}
