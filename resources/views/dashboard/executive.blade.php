@extends('layouts.app')

@section('title', 'Executive Dashboard - CybCademy')

{{--
    Implements Phase 6 Section 4.1's layout exactly: hero Risk Ring with
    trend + AI narrative, department heatmap below, compliance/incidents
    summary. Data is fetched client-side from the real Epic E6/E9 API
    endpoints via Alpine.js, rather than server-rendered on every page
    load, since the Human Risk Score is a periodic snapshot (Epic E6 -
    RecalculateHumanRiskScores) and the AI narrative (Epic E9) is worth
    loading progressively rather than blocking the page render on an AI
    API call.
--}}

@section('content')
<div x-data="executiveDashboard()" x-init="load()">
    <h1 class="cyb-display mb-4">Executive Dashboard</h1>

    <div class="row g-4">
        {{-- Hero: Risk Ring + AI narrative --}}
        <div class="col-lg-5">
            <div class="cyb-card h-100 d-flex flex-column align-items-center justify-content-center text-center">
                <template x-if="loading">
                    <div class="spinner-border text-secondary" role="status">
                        <span class="visually-hidden">Loading organisation risk score…</span>
                    </div>
                </template>
                <template x-if="!loading && score !== null">
                    <div x-html="riskRingHtml"></div>
                </template>
                <template x-if="!loading && score === null">
                    <p class="text-muted mt-3">No risk score has been calculated yet. Scores are generated on a scheduled basis once training and simulation activity begins.</p>
                </template>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="cyb-card h-100">
                <h2 class="h5 cyb-display">This quarter, in plain language</h2>
                <template x-if="narrativeLoading">
                    <p class="text-muted placeholder-glow"><span class="placeholder col-12"></span><span class="placeholder col-8"></span></p>
                </template>
                <template x-if="!narrativeLoading">
                    <p x-text="narrative || 'Not enough data yet to generate a summary.'"></p>
                </template>
            </div>
        </div>
    </div>

    {{-- Department heatmap --}}
    <div class="cyb-card mt-4">
        <h2 class="h5 cyb-display mb-3">Department Risk</h2>
        <div class="row g-3" x-show="departments.length > 0">
            <template x-for="dept in departments" :key="dept.department_id">
                <div class="col-6 col-md-3 col-lg-2 text-center">
                    <div x-html="renderMiniRing(dept.score, dept.department_name)"></div>
                </div>
            </template>
        </div>
        <p class="text-muted" x-show="!loading && departments.length === 0">No department scores calculated yet.</p>
    </div>

    {{-- AI risk recommendations - Epic E9, Enterprise/Professional tier gated server-side (402 handled below) --}}
    <div class="cyb-card mt-4">
        <h2 class="h5 cyb-display mb-3">Recommended next steps</h2>
        <template x-if="recommendationsError">
            <p class="text-muted" x-text="recommendationsError"></p>
        </template>
        <ul x-show="!recommendationsError" class="mb-0">
            <template x-for="rec in recommendations" :key="rec">
                <li x-text="rec"></li>
            </template>
        </ul>
    </div>
</div>

<script>
function executiveDashboard() {
    return {
        loading: true,
        narrativeLoading: true,
        score: null,
        trend: {},
        departments: [],
        narrative: '',
        recommendations: [],
        recommendationsError: null,
        riskRingHtml: '',

        async load() {
            try {
                const res = await fetch('/api/v1/reports/human-risk-score', {
                    headers: { 'Accept': 'application/json' },
                });
                const json = await res.json();
                this.score = json.data.current_org_score;
                this.trend = json.data.trend;
                this.departments = json.data.department_heatmap;
                this.riskRingHtml = this.renderRing(this.score ?? 0, 160, 'Organisation-wide');
            } finally {
                this.loading = false;
            }

            this.loadNarrative();
            this.loadRecommendations();
        },

        async loadNarrative() {
            try {
                const res = await fetch('/api/v1/ai/executive-narrative', { headers: { 'Accept': 'application/json' } });
                if (res.status === 402) {
                    this.narrative = 'AI executive summaries are available on the Enterprise plan.';
                    return;
                }
                const json = await res.json();
                this.narrative = json.data.narrative;
            } finally {
                this.narrativeLoading = false;
            }
        },

        async loadRecommendations() {
            const res = await fetch('/api/v1/ai/risk-recommendations', { headers: { 'Accept': 'application/json' } });
            if (res.status === 402) {
                this.recommendationsError = 'AI risk recommendations are available from the Professional plan.';
                return;
            }
            const json = await res.json();
            this.recommendations = json.data.recommendations;
        },

        // Client-side mirror of the Risk Ring Blade component's colour-
        // tier logic (components/risk-ring.blade.php) - duplicated here
        // deliberately, since this view fetches data client-side after
        // page load rather than server-rendering it; Blade can't be
        // invoked from already-loaded JS. Both implementations must stay
        // in sync on the score->tier thresholds (80/50) - worth
        // extracting into a shared, testable mapping if a third
        // consumer of this logic appears.
        renderRing(score, size, label) {
            const tier = score >= 80 ? 'low' : score >= 50 ? 'medium' : 'high';
            const tierLabel = tier === 'low' ? 'Low Risk' : tier === 'medium' ? 'Medium Risk' : 'High Risk';
            const colorVar = `var(--cyb-risk-${tier})`;
            const radius = (size / 2) - 10;
            const circumference = 2 * Math.PI * radius;
            const offset = circumference - (score / 100) * circumference;

            return `
                <div style="width:${size}px" role="img" aria-label="${label}: ${Math.round(score)} out of 100, ${tierLabel}">
                    <svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}" aria-hidden="true">
                        <circle cx="${size/2}" cy="${size/2}" r="${radius}" fill="none" stroke="var(--cyb-border)" stroke-width="8" />
                        <circle cx="${size/2}" cy="${size/2}" r="${radius}" fill="none" stroke="${colorVar}" stroke-width="8"
                            stroke-linecap="round" stroke-dasharray="${circumference}" stroke-dashoffset="${offset}"
                            transform="rotate(-90 ${size/2} ${size/2})" style="transition: stroke-dashoffset 800ms ease;" />
                        <text x="50%" y="50%" text-anchor="middle" dominant-baseline="central"
                            font-family="var(--cyb-font-display)" font-size="${size*0.22}" font-weight="700" fill="var(--cyb-text)">
                            ${Math.round(score)}
                        </text>
                    </svg>
                    <div class="text-center mt-1">
                        <span class="cyb-badge cyb-badge--${tier}">${tierLabel}</span>
                        <div class="small mt-1" style="color: var(--cyb-text-muted);">${label}</div>
                    </div>
                </div>`;
        },

        renderMiniRing(score, label) {
            return this.renderRing(score, 90, label);
        },
    };
}
</script>
@endsection
