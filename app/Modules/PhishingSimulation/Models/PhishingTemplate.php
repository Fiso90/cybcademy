<?php

declare(strict_types=1);

namespace App\Modules\PhishingSimulation\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Deliberately does NOT use App\Support\Traits\BelongsToTenant.
 *
 * That trait's global scope would hide platform-provided templates
 * (tenant_id IS NULL) from every tenant, which is the opposite of the
 * intended behaviour - see the migration docblock for the corresponding
 * RLS policy, which is written with the same "NULL OR mine" logic as the
 * scope below.
 */
final class PhishingTemplate extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'phishing_templates';

    protected $fillable = ['tenant_id', 'subject', 'body', 'ai_generated'];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant_or_platform', function (Builder $builder): void {
            $tenantId = \App\Support\TenantContext::current();

            $builder->where(function (Builder $q) use ($tenantId): void {
                $q->whereNull('tenant_id');

                if ($tenantId !== null) {
                    $q->orWhere('tenant_id', $tenantId);
                }
            });
        });
    }
}
