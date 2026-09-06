@extends('layouts.app')

@section('title', 'Report an Incident - CybCademy')

{{--
    Per Phase 2 SRS's note on StoreIncidentRequest (Epic E5): "any
    authenticated employee can report an incident... reporting a
    suspected phishing email must be fast and always accessible, not
    buried behind a permission." This screen is the visual expression of
    that principle - no filters, no required fields beyond the two the
    backend actually requires, no navigation away from the confirmation.
    The sidebar's "Report Phishing" link (layouts/app.blade.php) points
    here directly, one click from anywhere in the app.
--}}

@section('content')
<div x-data="incidentReport()" style="max-width: 560px;">
    <h1 class="cyb-display mb-2">Report an incident</h1>
    <p class="text-muted mb-4">Seen something suspicious? Tell us what happened - there's no wrong answer here, and reporting is never held against you.</p>

    <template x-if="!submitted">
        <form @submit.prevent="submit()" class="cyb-card">
            <div class="mb-3">
                <label class="form-label">What kind of incident is this?</label>
                <select class="form-select" x-model="category" required>
                    <option value="phishing">Suspected phishing</option>
                    <option value="suspicious_activity">Suspicious activity</option>
                    <option value="policy_violation">Policy violation</option>
                    <option value="other">Something else</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">What happened?</label>
                <textarea class="form-control" rows="5" x-model="description" required maxlength="5000"
                          placeholder="Describe what you saw - the more detail, the better, but a quick note is fine too."></textarea>
            </div>
            <p class="small text-danger" x-show="error" x-text="error"></p>
            <button type="submit" class="btn w-100" style="background-color: var(--cyb-accent); color: #fff;" :disabled="submitting">
                <span x-text="submitting ? 'Submitting…' : 'Submit report'"></span>
            </button>
        </form>
    </template>

    <template x-if="submitted">
        <div class="cyb-card text-center py-5">
            <i class="bi bi-check-circle-fill fs-1" style="color: var(--cyb-risk-low);"></i>
            <h2 class="cyb-display h5 mt-3">Thanks for reporting this</h2>
            <p class="text-muted">A Security Officer has been notified and will follow up if needed.</p>
            <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary mt-2">Back to dashboard</a>
        </div>
    </template>
</div>

<script>
function incidentReport() {
    return {
        category: 'phishing',
        description: '',
        submitting: false,
        submitted: false,
        error: null,

        async submit() {
            this.submitting = true;
            this.error = null;

            const res = await fetch('/api/v1/incidents', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ category: this.category, description: this.description }),
            });

            if (res.ok) {
                this.submitted = true;
            } else {
                const json = await res.json();
                this.error = json.errors?.[0]?.message || 'Something went wrong submitting this - please try again.';
            }
            this.submitting = false;
        },
    };
}
</script>
@endsection
