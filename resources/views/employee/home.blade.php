@extends('layouts.app')

@section('title', 'My Training - CybCademy')

{{--
    Phase 6 Section 4.3: "Minimal, task-focused" - this is the persona
    the design review checklist (Phase 6 Risks / Phase 9 Sprint plan) was
    written to protect from compliance-dashboard complexity creep. No
    tables, no filters, no data-density - just what's assigned, what's
    due, and a fast path to report something suspicious.
--}}

@section('content')
<div x-data="employeeHome()" x-init="load()">
    <h1 class="cyb-display mb-4">My Training</h1>

    <template x-if="loading">
        <p class="text-muted">Loading your assignments…</p>
    </template>

    {{-- Empty state is an invitation to act, per Phase 6 Section 9 -
         never a bare "no data." --}}
    <template x-if="!loading && assignments.length === 0">
        <div class="cyb-card text-center py-5">
            <i class="bi bi-mortarboard fs-1" style="color: var(--cyb-text-muted);"></i>
            <p class="mt-3 mb-0">Nothing assigned yet. Check back soon, or browse the catalogue.</p>
            <a href="{{ route('courses.index') }}" class="btn btn-sm mt-3" style="background-color: var(--cyb-accent); color: #fff;">
                Browse courses
            </a>
        </div>
    </template>

    <div class="row g-3" x-show="!loading && assignments.length > 0">
        <template x-for="a in assignments" :key="a.id">
            <div class="col-md-6 col-lg-4">
                <div class="cyb-card h-100">
                    <h2 class="h6 cyb-display" x-text="a.course.title"></h2>
                    <div class="progress mb-2" style="height: 6px;">
                        <div class="progress-bar" role="progressbar"
                             :style="`width: ${a.status === 'completed' ? 100 : (a.status === 'in_progress' ? 50 : 0)}%; background-color: var(--cyb-accent);`">
                        </div>
                    </div>
                    <p class="small mb-2" :style="a.is_overdue ? 'color: var(--cyb-risk-high); font-weight: 600;' : 'color: var(--cyb-text-muted);'">
                        <span x-show="a.status === 'completed'">Completed</span>
                        <span x-show="a.status !== 'completed' && a.due_date" x-text="`Due ${new Date(a.due_date).toLocaleDateString()}`"></span>
                    </p>
                    <a :href="`/courses/${a.course.id}`" class="btn btn-sm btn-outline-secondary w-100">
                        <span x-text="a.status === 'completed' ? 'Review' : (a.status === 'in_progress' ? 'Continue' : 'Start')"></span>
                    </a>
                </div>
            </div>
        </template>
    </div>
</div>

<script>
function employeeHome() {
    return {
        loading: true,
        assignments: [],
        async load() {
            // Course assignment list endpoint (Phase 8 Section 5:
            // GET /employees/{id}/courses) - self-scoped via the
            // authenticated user, no ID needed in the URL for "my own"
            // assignments in this view.
            const res = await fetch('/api/v1/me/courses', { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            this.assignments = json.data;
            this.loading = false;
        },
    };
}
</script>
@endsection
