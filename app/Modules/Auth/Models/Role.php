<?php

declare(strict_types=1);

namespace App\Modules\Auth\Models;

use App\Support\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A tenant-scoped role (e.g. "Organisation Administrator" for a specific
 * tenant). Roles are per-tenant rows, but the platform's permission
 * catalogue itself (Permission model) is global.
 *
 * @see /mnt/user-data/outputs/CybCademy_Phase5_Database_Design.md Section 3.3
 */
final class Role extends Model
{
    use HasUuids;
    use BelongsToTenant;

    protected $table = 'roles';

    protected $fillable = ['tenant_id', 'name', 'slug'];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_has_permissions');
    }
}
