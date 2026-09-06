@extends('layouts.app')

@section('title', 'Phishing Simulation - CybCademy')

{{--
    Phase 6 Section 1 flagged this alongside Course Builder/Player as
    needing genuine interactivity ("live campaign results"). Deliberately
    does NOT branch on the viewer's role to decide what shape of results
    to render - it renders whatever PhishingCampaignController::results()
    (Epic E4's BR-4 enforcement point) sends back, whether that's
    individual rows or department aggregates. This view has no idea which
    it's looking at beyond checking which fields are present, which is
    the correct amount of role-awareness for a screen to have when the
    actual access-control decision already happened server-side, in
    exactly the one place (PhishingResultsRepository) it's supposed to.
--}}

@section('content')
<div x-data="phishingScreen()" x-init="load()">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="cyb-display mb-0">Phishing Simulation</h1>
        @can('phishing_campaigns.view')
            <button class="btn btn-outline-secondary btn-sm" @click="showBuilder = !showBuilder">
                <i class="bi bi-plus-lg me-1"></i>New campaign
            </button>
        @endcan
    </div>

    {{-- Campaign builder --}}
    <div class="cyb-card mb-4" x-show="showBuilder" x-cloak>
        <h2 class="h6 cyb-display">New phishing simulation campaign</h2>
        <form @submit.prevent="createCampaign()">
            <div class="row g-2 mb-2">
                <div class="col-md-6">
                    <input type="text" class="form-control form-control-sm" placeholder="Campaign name" x-model="newCampaign.name" required>
                </div>
                <div class="col-md-6">
                    <select class="form-select form-select-sm" x-model="newCampaign.template_id" required>
                        <option value="" disabled selected>Choose a template</option>
                        <template x-for="t in templates" :key="t.id">
                            <option :value="t.id" x-text="t.subject"></option>
                        </template>
                    </select>
                </div>
            </div>

            {{-- AI template generation - Epic E9, tier-gated (402 handled) --}}
            <div class="border rounded p-2 mb-3" style="border-color: var(--cyb-border) !important;">
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control" placeholder="Describe a scenario for AI to generate (e.g. 'fake IT password reset')" x-model="aiScenario">
                    <button class="btn btn-outline-secondary" type="button" @click="generateTemplate()" :disabled="generatingTemplate">
                        <span x-text="generatingTemplate ? 'Generating…' : 'Generate with AI'"></span>
                    </button>
                </div>
                <p class="small text-danger mt-1 mb-0" x-show="templateError" x-text="templateError"></p>
            </div>

            <button type="submit" class="btn btn-sm" style="background-color: var(--cyb-accent); color: #fff;">
                Save as draft
            </button>
        </form>
    </div>

    {{-- Campaign list --}}
    <div class="row g-3">
        <template x-for="c in campaigns" :key="c.id">
            <div class="col-md-6">
                <div class="cyb-card h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h2 class="h6 cyb-display mb-0" x-text="c.name"></h2>
                        <span class="cyb-badge" :class="c.status === 'sent' ? 'cyb-badge--low' : 'cyb-badge--medium'" x-text="c.status"></span>
                    </div>

                    <button x-show="c.status === 'draft'" class="btn btn-sm btn-outline-secondary mb-2" @click="launch(c)">
                        Launch campaign
                    </button>

                    <button x-show="c.status !== 'draft'" class="btn btn-sm btn-outline-secondary mb-2" @click="viewResults(c)">
                        View results
                    </button>
                </div>
            </div>
        </template>
    </div>

    {{-- Live results - polls every 10s while open --}}
    <div class="cyb-card mt-4" x-show="activeResultsCampaign" x-cloak>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 cyb-display mb-0">Results</h2>
            <button class="btn btn-sm btn-link" @click="closeResults()">Close</button>
        </div>

        <template x-if="resultsAreIndividual">
            <table class="table table-sm mb-0">
                <thead><tr><th>Employee</th><th>Event</th><th>When</th></tr></thead>
                <tbody>
                    <template x-for="r in results" :key="r.id">
                        <tr>
                            <td x-text="r.user.name"></td>
                            <td x-text="r.event_type"></td>
                            <td x-text="new Date(r.event_at).toLocaleString()"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </template>
        <template x-if="!resultsAreIndividual && results.length > 0">
            <div>
                <p class="small text-muted">Department-level results (your role sees aggregated data only, per organisation policy).</p>
                <table class="table table-sm mb-0">
                    <thead><tr><th>Department</th><th>Event</th><th>Count</th></tr></thead>
                    <tbody>
                        <template x-for="(r, i) in results" :key="i">
                            <tr>
                                <td x-text="r.department_id || 'Unassigned'"></td>
                                <td x-text="r.event_type"></td>
                                <td x-text="r.event_count"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>
    </div>
</div>

<script>
function phishingScreen() {
    return {
        campaigns: [],
        templates: [],
        showBuilder: false,
        newCampaign: { name: '', template_id: '', target_scope: {} },
        aiScenario: '',
        generatingTemplate: false,
        templateError: null,
        activeResultsCampaign: null,
        results: [],
        resultsAreIndividual: false,
        pollHandle: null,

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]').content;
        },

        async load() {
            const [campaignsRes, templatesRes] = await Promise.all([
                fetch('/api/v1/phishing-campaigns', { headers: { 'Accept': 'application/json' } }),
                fetch('/api/v1/phishing-templates', { headers: { 'Accept': 'application/json' } }),
            ]);
            this.campaigns = (await campaignsRes.json()).data;
            this.templates = (await templatesRes.json()).data;
        },

        async createCampaign() {
            const res = await fetch('/api/v1/phishing-campaigns', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': this.csrfToken(), 'Content-Type': 'application/json' },
                body: JSON.stringify(this.newCampaign),
            });
            if (res.ok) {
                this.showBuilder = false;
                this.load();
            }
        },

        async generateTemplate() {
            this.generatingTemplate = true;
            this.templateError = null;
            try {
                const res = await fetch('/api/v1/ai/phishing-templates/generate', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrfToken(), 'Content-Type': 'application/json' },
                    body: JSON.stringify({ scenario: this.aiScenario }),
                });
                if (res.status === 402) {
                    this.templateError = 'AI phishing template generation is available on the Enterprise plan.';
                    return;
                }
                const json = await res.json();
                if (!res.ok) {
                    this.templateError = json.errors?.[0]?.message || 'Could not generate a template.';
                    return;
                }
                this.templates.push(json.data);
                this.newCampaign.template_id = json.data.id;
            } finally {
                this.generatingTemplate = false;
            }
        },

        async launch(campaign) {
            await fetch(`/api/v1/phishing-campaigns/${campaign.id}/launch`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': this.csrfToken() },
            });
            this.load();
        },

        async viewResults(campaign) {
            this.activeResultsCampaign = campaign;
            await this.fetchResults();
            this.pollHandle = setInterval(() => this.fetchResults(), 10000);
        },

        async fetchResults() {
            const res = await fetch(`/api/v1/phishing-campaigns/${this.activeResultsCampaign.id}/results`, {
                headers: { 'Accept': 'application/json' },
            });
            const json = await res.json();
            this.results = json.data;
            this.resultsAreIndividual = this.results.length > 0 && 'user' in this.results[0];
        },

        closeResults() {
            this.activeResultsCampaign = null;
            clearInterval(this.pollHandle);
        },
    };
}
</script>
@endsection
