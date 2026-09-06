<?php

declare(strict_types=1);

namespace App\Modules\Employee\Repositories;

use App\Modules\Employee\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

/**
 * Encapsulates all query logic for employees.
 *
 * Per Phase 4 Section 4, the Repository layer is where the tenant-scoping
 * enforcement point becomes auditable in one place per module - every
 * method here relies on the BelongsToTenant global scope on the User
 * model (Phase 5 Section 6 / Phase 7 Section 2 A01), and nothing in this
 * class ever needs to add its own ->where('tenant_id', ...) clause, which
 * is precisely the point: the scope is automatic, not something each
 * query author must remember.
 */
final class EmployeeRepository
{
    public function paginate(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = User::query()->with('department');

        if (! empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function findOrFail(string $id): User
    {
        return User::findOrFail($id);
    }

    public function create(array $attributes): User
    {
        return User::create($attributes);
    }

    public function update(User $user, array $attributes): User
    {
        $user->update($attributes);

        return $user->fresh();
    }

    public function softDelete(User $user): void
    {
        $user->update(['status' => 'inactive']);
        $user->delete();
    }
}
