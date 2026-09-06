<?php

declare(strict_types=1);

namespace App\Modules\KnowledgeBase\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Deliberately does not use BelongsToTenant - same reasoning as
 * PhishingTemplate (Epic E4): platform-authored articles (tenant_id IS
 * NULL) must remain visible to every tenant.
 */
final class KnowledgeBaseArticle extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'knowledge_base_articles';

    protected $fillable = ['tenant_id', 'title', 'body', 'category', 'published'];

    protected $casts = ['published' => 'boolean'];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant_or_platform', function (Builder $builder): void {
            $tenantId = \App\Support\TenantContext::current();

            $builder->where(function (Builder $q) use ($tenantId): void {
                $q->whereNull('tenant_id');
                if ($tenantId !== null) {
                    $q->orWhere('tenant_id', $tenantId);
                }
            })->where('published', true);
            // Unpublished drafts (including a tenant's own unpublished
            // drafts) are excluded by this global scope entirely - the
            // authoring/draft-review flow queries with
            // ->withoutGlobalScope('tenant_or_platform') explicitly,
            // same escape-hatch pattern as BelongsToTenant.
        });
    }
}
