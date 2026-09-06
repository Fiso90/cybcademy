<?php

declare(strict_types=1);

namespace App\Modules\Employee\Controllers;

use App\Modules\Employee\Requests\StoreEmployeeRequest;
use App\Modules\Employee\Services\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Implements the Employee endpoints specified in Phase 8 Section 4.
 *
 * Thin by design: validates via Form Request (authorization + rules),
 * delegates all logic to EmployeeService, and shapes the response envelope
 * defined in Phase 8 Section 1 ({ data, meta, errors }).
 */
final class EmployeeController
{
    public function __construct(
        private readonly EmployeeService $employeeService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $employees = $this->employeeService->list(
            $request->only(['department_id', 'status'])
        );

        return response()->json([
            'data' => $employees->items(),
            'meta' => [
                'page' => $employees->currentPage(),
                'per_page' => $employees->perPage(),
                'total' => $employees->total(),
            ],
            'errors' => [],
        ]);
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $employee = $this->employeeService->create($request->validated());

        return response()->json(['data' => $employee, 'meta' => [], 'errors' => []], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $this->authorizeManage($request);

        $employee = $this->employeeService->update($id, $request->only(['name', 'department_id', 'status']));

        return response()->json(['data' => $employee, 'meta' => [], 'errors' => []]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->authorizeManage($request);

        $this->employeeService->deactivate($id);

        return response()->json(['data' => null, 'meta' => [], 'errors' => []], 204);
    }

    public function myCourses(Request $request): JsonResponse
    {
        // Self-scoped by the authenticated user - no :id in the route,
        // deliberately, so an Employee's own "My Training" view (Phase 11
        // resources/views/employee/home.blade.php) never needs to know or
        // pass its own user ID, and can never be pointed at someone
        // else's assignments by tampering with a URL parameter.
        $assignments = \App\Modules\LMS\Models\CourseAssignment::where('user_id', $request->user()->id)
            ->with('course:id,title')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'status' => $a->status,
                'due_date' => $a->due_date,
                'is_overdue' => $a->isOverdue(),
                'course' => $a->course,
            ]);

        return response()->json(['data' => $assignments, 'meta' => [], 'errors' => []]);
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless($request->user()->hasPermission('employees.manage'), 403);
    }
}
