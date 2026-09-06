@extends('layouts.app')

@section('title', 'Compliance Dashboard - CybCademy')

{{--
    Phase 6 Section 4.2: "Table-dense view: filterable by department/
    course/policy, completion status columns, overdue flags in red,
    one-click export button prominent in the top-right (this is the
    highest-frequency action for this persona, per Phase 3 user flows)."
    Built as the direct opposite of employee/home.blade.php's minimalism -
    that contrast is the point (Phase 6 Section 1's two-persona design
    principle).
--}}

@section('content')
<div x-data="complianceDashboard()" x-init="load()">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="cyb-display mb-0">Compliance Dashboard</h1>
        {{-- One-click export - Phase 6 Section 4.2's "highest-frequency
             action for this persona," placed prominently top-right, not
             buried below the fold. Hits Epic E7's real async export
             endpoint. --}}
        <button class="btn" style="background-color: var(--cyb-accent); color: #fff;"
                @click="requestExport()" :disabled="exporting">
            <i class="bi bi-download me-1"></i>
            <span x-text="exporting ? 'Preparing export…' : 'Export evidence'"></span>
        </button>
    </div>

    <template x-if="exportStatus">
        <div class="alert" :class="exportStatus === 'completed' ? 'alert-success' : 'alert-info'" role="status">
            <span x-show="exportStatus !== 'completed'">Generating your evidence export — this typically takes under 5 minutes. You can keep working; we'll update this when it's ready.</span>
            <span x-show="exportStatus === 'completed'">
                Export ready.
                <a :href="exportDownloadUrl" class="alert-link">Download now</a>
                (link expires in 15 minutes).
            </span>
        </div>
    </template>

    {{-- Summary cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="cyb-card text-center">
                <div class="cyb-display fs-3" x-text="summary.completed ?? '—'"></div>
                <div class="small text-muted">Completed</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="cyb-card text-center">
                <div class="cyb-display fs-3" x-text="summary.in_progress ?? '—'"></div>
                <div class="small text-muted">In progress</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="cyb-card text-center">
                <div class="cyb-display fs-3" x-text="summary.assigned ?? '—'"></div>
                <div class="small text-muted">Not started</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="cyb-card text-center">
                <div class="cyb-display fs-3" style="color: var(--cyb-risk-high);" x-text="summary.overdue ?? '—'"></div>
                <div class="small text-muted">Overdue</div>
            </div>
        </div>
    </div>

    {{-- Employee table, filterable by department per Phase 6 Section 4.2 --}}
    <div class="cyb-card">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="h5 cyb-display mb-0">Employees</h2>
            <select class="form-select form-select-sm" style="width: auto;" x-model="departmentFilter" @change="load()">
                <option value="">All departments</option>
                <template x-for="d in departments" :key="d.id">
                    <option :value="d.id" x-text="d.name"></option>
                </template>
            </select>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Risk score</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="emp in employees" :key="emp.id">
                        <tr>
                            <td x-text="emp.name"></td>
                            <td x-text="emp.department_name || '—'"></td>
                            <td>
                                <span class="cyb-badge"
                                      :class="riskBadgeClass(emp.cached_human_risk_score)"
                                      x-text="emp.cached_human_risk_score !== null ? Math.round(emp.cached_human_risk_score) : 'Not yet calculated'">
                                </span>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="!loading && employees.length === 0">
                        <td colspan="3" class="text-center text-muted py-4">No employees match this filter.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Policy acknowledgement summary --}}
    <div class="cyb-card mt-4">
        <h2 class="h5 cyb-display mb-3">Policy Acknowledgement</h2>
        <table class="table mb-0">
            <thead><tr><th>Policy</th><th>Acknowledged</th><th>Outstanding</th></tr></thead>
            <tbody>
                <template x-for="p in policies" :key="p.policy_id">
                    <tr>
                        <td x-text="p.title"></td>
                        <td x-text="p.acknowledged_count"></td>
                        <td :style="p.outstanding_count > 0 ? 'color: var(--cyb-risk-high); font-weight: 600;' : ''" x-text="p.outstanding_count"></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>

<script>
function complianceDashboard() {
    return {
        loading: true,
        summary: {},
        employees: [],
        policies: [],
        departments: [], // populated from the department filter dropdown's own endpoint in a full build; left empty here since departments.index isn't in this slice's scope
        departmentFilter: '',
        exporting: false,
        exportStatus: null,
        exportDownloadUrl: null,

        async load() {
            this.loading = true;
            const url = new URL('/api/v1/reports/compliance', window.location.origin);
            if (this.departmentFilter) url.searchParams.set('department_id', this.departmentFilter);

            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            this.summary = json.data.course_completion;
            this.employees = json.data.employees;
            this.policies = json.data.policy_acknowledgement;
            this.loading = false;
        },

        riskBadgeClass(score) {
            if (score === null) return 'cyb-badge--medium';
            return score >= 80 ? 'cyb-badge--low' : score >= 50 ? 'cyb-badge--medium' : 'cyb-badge--high';
        },

        async requestExport() {
            this.exporting = true;
            const res = await fetch('/api/v1/audit/export', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({}),
            });
            const json = await res.json();
            this.pollExport(json.data.export_id);
        },

        async pollExport(exportId) {
            const poll = async () => {
                const res = await fetch(`/api/v1/audit/export/${exportId}`, { headers: { 'Accept': 'application/json' } });
                const json = await res.json();
                this.exportStatus = json.data.status;

                if (json.data.status === 'completed') {
                    this.exportDownloadUrl = json.data.download_url;
                    this.exporting = false;
                } else if (json.data.status === 'failed') {
                    this.exporting = false;
                } else {
                    setTimeout(poll, 5000); // poll every 5s against the async job pattern from Phase 8/Epic E7
                }
            };
            poll();
        },
    };
}
</script>
@endsection
