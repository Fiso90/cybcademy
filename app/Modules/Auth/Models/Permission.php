<?php

declare(strict_types=1);

namespace App\Modules\Auth\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * A platform-wide permission (e.g. "employees.manage", "audit.export").
 * Global, not tenant-scoped - the permission catalogue itself is fixed by
 * the platform and is not customer-editable, per Phase 5 Section 3.3.
 */
final class Permission extends Model
{
    use HasUuids;

    protected $table = 'permissions';

    protected $fillable = ['name', 'description'];

    public $timestamps = true;
    protected $touch = [];
}
