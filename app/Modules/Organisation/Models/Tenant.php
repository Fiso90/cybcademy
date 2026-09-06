<?php

declare(strict_types=1);

namespace App\Modules\Organisation\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Represents an organisation subscribed to CybCademy.
 *
 * Deliberately does NOT use App\Support\Traits\BelongsToTenant - this model
 * IS the tenant boundary, so it has no tenant_id column to scope against.
 *
 * @see /mnt/user-data/outputs/CybCademy_Phase5_Database_Design.md Section 3.1
 */
final class Tenant extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'tenants';

    protected $fillable = [
        'name',
        'industry',
        'subscription_tier',
        'compliance_frameworks',
    ];

    protected $casts = [
        'compliance_frameworks' => 'array',
    ];
}
