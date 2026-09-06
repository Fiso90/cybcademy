<?php

declare(strict_types=1);

namespace App\Modules\Employee\Services;

use App\Modules\Employee\Models\User;
use App\Modules\Employee\Repositories\EmployeeRepository;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Business logic for employee management, including the lifecycle events
 * referenced in Phase 2 FR-2.4 (onboarding/offboarding triggers automatic
 * training/policy assignment or revocation).
 *
 * The actual course/policy assignment side-effects are dispatched as
 * events (EmployeeOnboarded, EmployeeOffboarded) and handled by listeners
 * in the LMS and Policy modules (Epics E2/E3, per Phase 9) rather than
 * this service reaching directly into those modules' internals - keeping
 * module boundaries clean per the folder structure in Phase 4 Section 3.
 */
final class EmployeeService
{
    public function __construct(
        private readonly EmployeeRepository $employees,
        private readonly AuditLogger $auditLogger,
    ) {
    }

    public function list(array $filters): LengthAwarePaginator
    {
        return $this->employees->paginate($filters);
    }

    public function create(array $attributes): User
    {
        $attributes['tenant_id'] = TenantContext::current();
        $attributes['status'] = 'invited';

        // Temporary password - real flow sends an invitation link with a
        // time-limited token for the employee to set their own password;
        // included here only to show where hashing occurs (never assign
        // password_hash directly from raw request input elsewhere).
        $attributes['password_hash'] = Hash::make(Str::random(32));

        $employee = $this->employees->create($attributes);

        $this->auditLogger->log(
            tenantId: $employee->tenant_id,
            actorUserId: auth()->id(),
            action: 'employee.created',
            resourceType: 'user',
            resourceId: $employee->id,
            afterState: ['email' => $employee->email, 'department_id' => $employee->department_id],
        );

        event(new \App\Modules\Employee\Events\EmployeeOnboarded($employee));

        return $employee;
    }

    public function update(string $id, array $attributes): User
    {
        $employee = $this->employees->findOrFail($id);
        $before = $employee->only(array_keys($attributes));

        $updated = $this->employees->update($employee, $attributes);

        $this->auditLogger->log(
            tenantId: $updated->tenant_id,
            actorUserId: auth()->id(),
            action: 'employee.updated',
            resourceType: 'user',
            resourceId: $updated->id,
            beforeState: $before,
            afterState: $updated->only(array_keys($attributes)),
        );

        return $updated;
    }

    public function deactivate(string $id): void
    {
        $employee = $this->employees->findOrFail($id);

        $this->employees->softDelete($employee);

        $this->auditLogger->log(
            tenantId: $employee->tenant_id,
            actorUserId: auth()->id(),
            action: 'employee.deactivated',
            resourceType: 'user',
            resourceId: $employee->id,
        );

        event(new \App\Modules\Employee\Events\EmployeeOffboarded($employee));
    }
}
