<?php

declare(strict_types=1);

namespace App\Modules\Employee\Models;

use App\Modules\Organisation\Models\Department;
use App\Modules\Organisation\Models\Tenant;
use App\Support\Traits\BelongsToTenant;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * The core authenticatable user model.
 *
 * Password hashing uses Argon2id (Phase 7 Section 3) via Laravel's
 * configured default hasher - `password_hash` is never assigned directly
 * without going through the AuthService, which is responsible for hashing.
 *
 * @see /mnt/user-data/outputs/CybCademy_Phase5_Database_Design.md Section 3.2
 * @see /mnt/user-data/outputs/CybCademy_Phase7_Security_Architecture.md Section 3
 */
final class User extends Authenticatable
{
    use HasApiTokens;
    use HasUuids;
    use SoftDeletes;
    use Notifiable;
    use BelongsToTenant;

    protected $table = 'users';

    protected $fillable = [
        'tenant_id',
        'department_id',
        'name',
        'email',
        'status',
    ];

    protected $hidden = [
        'password_hash',
        'mfa_secret',
    ];

    protected $casts = [
        'mfa_enabled' => 'boolean',
        'mfa_secret' => 'encrypted', // Phase 7 Section 5 - encrypted at rest
        'cached_human_risk_score' => 'decimal:2',
        'last_login_at' => 'datetime',
    ];

    /**
     * Laravel's Authenticatable contract expects getAuthPassword(); we map
     * it explicitly to our `password_hash` column since we deliberately
     * did not name the column `password`, to make its hashed nature
     * unambiguous in the schema itself.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Modules\Auth\Models\Role::class,
            'model_has_roles',
            'model_id',
            'role_id'
        )->wherePivot('model_type', 'user');
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles()->where('slug', $slug)->exists();
    }

    public function hasPermission(string $permissionName): bool
    {
        return $this->roles()
            ->whereHas('permissions', fn ($q) => $q->where('name', $permissionName))
            ->exists();
    }

    /**
     * Added in Epic E3 (Policy Management) - relation used by
     * PolicyService::usersWithOutstandingAcknowledgement() to find users
     * who have not yet acknowledged a given policy version (FR-4.3).
     */
    public function policyAcknowledgements(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\Policy\Models\PolicyAcknowledgement::class);
    }
}
