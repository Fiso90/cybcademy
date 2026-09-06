@extends('layouts.app')

@section('title', 'Audit Centre - CybCademy')

{{--
    Same table + filter + export pattern proven in
    dashboard/compliance.blade.php, applied to the raw audit log instead
    of the compliance summary - reuses the identical async-export-and-poll
    approach against Epic E7's real /audit/export endpoints, rather than
    inventing a second pattern for what is functionally the same
    interaction (request an export, wait, download).
--}}

@section('content')
<div x-data="auditCentre()" x-init="load()">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="cyb-display mb-0">Audit Centre</h1>
        <button class="btn" style="background-color: var(--cyb-accent); color: #fff;"
                @click="requestExport()" :disabled="exporting">
            <i class="bi bi-download me-1"></i>
            <span x-text="exporting ? 'Preparing export…' : 'Export evidence'"></span>
        </button>
    </div>

    <template x-if="exportStatus">
        <div class="alert" :class="exportStatus === 'completed' ? 'alert-success' : 'alert-info'" role="status">
            <span x-show="exportStatus !== 'completed'">Generating your evidence export — typically under 5 minutes. You can keep working.</span>
            <span x-show="exportStatus === 'completed'">
                Export ready (<span x-text="exportRecordCount"></span> records).
                <a :href="exportDownloadUrl" class="alert-link">Download now</a> (link expires in 15 minutes).
            </span>
        </div>
    </template>

    {{-- Filters --}}
    <div class="cyb-card mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">From</label>
                <input type="date" class="form-control form-control-sm" x-model="filters.date_from">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">To</label>
                <input type="date" class="form-control form-control-sm" x-model="filters.date_to">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Resource type</label>
                <input type="text" class="form-control form-control-sm" placeholder="e.g. user, policy" x-model="filters.resource_type">
            </div>
            <div class="col-md-3">
                <button class="btn btn-sm btn-outline-secondary w-100" @click="load()">Apply filters</button>
            </div>
        </div>
    </div>

    {{-- Log table --}}
    <div class="cyb-card">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead>
                    <tr><th>When</th><th>Actor</th><th>Action</th><th>Resource</th></tr>
                </thead>
                <tbody>
                    <template x-for="log in logs" :key="log.id">
                        <tr>
                            <td class="cyb-mono small" x-text="new Date(log.occurred_at).toLocaleString()"></td>
                            <td x-text="log.actor_name || 'System'"></td>
                            <td><span class="cyb-mono small" x-text="log.action"></span></td>
                            <td class="small text-muted" x-text="`${log.resource_type}${log.resource_id ? ' #' + log.resource_id.slice(0,8) : ''}`"></td>
                        </tr>
                    </template>
                    <tr x-show="!loading && logs.length === 0">
                        <td colspan="4" class="text-center text-muted py-4">No audit log entries match these filters.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function auditCentre() {
    return {
        loading: true,
        logs: [],
        filters: { date_from: '', date_to: '', resource_type: '' },
        exporting: false,
        exportStatus: null,
        exportDownloadUrl: null,
        exportRecordCount: null,

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]').content;
        },

        async load() {
            this.loading = true;
            const url = new URL('/api/v1/audit-logs', window.location.origin);
            Object.entries(this.filters).forEach(([k, v]) => { if (v) url.searchParams.set(k, v); });

            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            this.logs = json.data;
            this.loading = false;
        },

        async requestExport() {
            this.exporting = true;
            const res = await fetch('/api/v1/audit/export', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken() },
                body: JSON.stringify(this.filters),
            });
            const json = await res.json();
            this.pollExport(json.data.export_id);
        },

        async pollExport(exportId) {
            const poll = async () => {
                const res = await fetch(`/api/v1/audit/export/${exportId}`, { headers: { 'Accept': 'application/json' } });
                const json = await res.json();
                this.exportStatus = json.data.status;
                this.exportRecordCount = json.data.record_count;

                if (json.data.status === 'completed') {
                    this.exportDownloadUrl = json.data.download_url;
                    this.exporting = false;
                } else if (json.data.status === 'failed') {
                    this.exporting = false;
                } else {
                    setTimeout(poll, 5000);
                }
            };
            poll();
        },
    };
}
</script>
@endsection
