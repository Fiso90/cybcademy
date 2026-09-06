@extends('layouts.app')

@section('title', 'Policies - CybCademy')

{{--
    Serves two personas at once, per Phase 6 Section 1's two-audience
    principle: an ordinary employee sees published policies and a single
    "Acknowledge" action per unacknowledged one (fast, low-friction); a
    Compliance Officer/Admin additionally sees upload/publish controls.
    Both views are driven by the same @can check pattern as the sidebar
    (layouts/app.blade.php) - visibility of the management controls below
    and the nav item that links here both derive from the same
    'policies.manage' permission, so they can't disagree.
--}}

@section('content')
<div x-data="policiesScreen()" x-init="load()">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="cyb-display mb-0">Policies</h1>
        @can('policies.manage')
            <button class="btn btn-outline-secondary btn-sm" @click="showUpload = !showUpload">
                <i class="bi bi-upload me-1"></i>Upload new policy
            </button>
        @endcan
    </div>

    @can('policies.manage')
        <div class="cyb-card mb-4" x-show="showUpload" x-cloak>
            <h2 class="h6 cyb-display">Upload a policy document</h2>
            <form @submit.prevent="upload()">
                <div class="mb-2">
                    <label class="form-label small">Title</label>
                    <input type="text" class="form-control form-control-sm" x-model="uploadTitle" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small">File (PDF or DOCX, max 10MB)</label>
                    <input type="file" class="form-control form-control-sm" accept=".pdf,.docx"
                           @change="uploadFile = $event.target.files[0]" required>
                </div>
                <button type="submit" class="btn btn-sm" style="background-color: var(--cyb-accent); color: #fff;">Upload as new draft version</button>
            </form>
        </div>
    @endcan

    <template x-if="loading">
        <p class="text-muted">Loading policies…</p>
    </template>

    <div class="row g-3" x-show="!loading && policies.length > 0">
        <template x-for="p in policies" :key="p.id">
            <div class="col-md-6">
                <div class="cyb-card h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h2 class="h6 cyb-display mb-1" x-text="p.title"></h2>
                            <span class="cyb-mono small text-muted" x-text="`v${p.version}`"></span>
                        </div>
                        <span class="cyb-badge" :class="p.published_at ? 'cyb-badge--low' : 'cyb-badge--medium'"
                              x-text="p.published_at ? 'Published' : 'Draft'"></span>
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        @can('policies.manage')
                            <button x-show="!p.published_at" class="btn btn-sm btn-outline-secondary" @click="publish(p)">
                                Publish
                            </button>
                        @endcan
                        <button x-show="p.published_at && !p.acknowledged_by_me" class="btn btn-sm"
                                style="background-color: var(--cyb-accent); color: #fff;"
                                @click="acknowledge(p)">
                            I've read and understood this policy
                        </button>
                        <span x-show="p.acknowledged_by_me" class="small text-muted align-self-center">
                            <i class="bi bi-check-circle-fill" style="color: var(--cyb-risk-low);"></i> Acknowledged
                        </span>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <p class="text-muted" x-show="!loading && policies.length === 0">No policies published yet.</p>
</div>

<script>
function policiesScreen() {
    return {
        loading: true,
        policies: [],
        showUpload: false,
        uploadTitle: '',
        uploadFile: null,

        async load() {
            const res = await fetch('/api/v1/policies', { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            // acknowledged_by_me is now returned directly by
            // GET /policies (PolicyController::index(), fixed in this
            // same Phase 11 drop) - no client-side workaround needed.
            this.policies = json.data;
            this.loading = false;
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]').content;
        },

        async upload() {
            const formData = new FormData();
            formData.append('title', this.uploadTitle);
            formData.append('file', this.uploadFile);

            await fetch('/api/v1/policies', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': this.csrfToken() },
                body: formData,
            });

            this.showUpload = false;
            this.uploadTitle = '';
            this.load();
        },

        async publish(policy) {
            await fetch(`/api/v1/policies/${policy.id}/publish`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': this.csrfToken() },
            });
            this.load();
        },

        async acknowledge(policy) {
            const res = await fetch(`/api/v1/policies/${policy.id}/acknowledge`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': this.csrfToken() },
            });
            if (res.ok) {
                policy.acknowledged_by_me = true;
            }
        },
    };
}
</script>
@endsection
