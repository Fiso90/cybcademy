@extends('layouts.app')

@section('title', 'Training Catalogue - CybCademy')

@section('content')
<div x-data="courseCatalogue()" x-init="load()">
    <h1 class="cyb-display mb-4">Training Catalogue</h1>

    <template x-if="loading">
        <p class="text-muted">Loading courses…</p>
    </template>

    <div class="row g-3" x-show="!loading && courses.length > 0">
        <template x-for="c in courses" :key="c.id">
            <div class="col-md-6 col-lg-4">
                <div class="cyb-card h-100 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h2 class="h6 cyb-display mb-0" x-text="c.title"></h2>
                        <span class="cyb-badge" :class="c.status === 'published' ? 'cyb-badge--low' : 'cyb-badge--medium'"
                              x-text="c.status"></span>
                    </div>
                    <p class="small text-muted flex-grow-1" x-text="c.description || 'No description provided.'"></p>

                    <div class="d-flex gap-2">
                        @can('courses.manage')
                            <a :href="`/courses/${c.id}/builder`" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <button x-show="c.status === 'draft'" class="btn btn-sm btn-outline-secondary" @click="publish(c)">
                                Publish
                            </button>
                        @endcan
                        <a x-show="c.status === 'published'" :href="`/courses/${c.id}/play`" class="btn btn-sm"
                           style="background-color: var(--cyb-accent); color: #fff;">Start</a>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <p class="text-muted" x-show="!loading && courses.length === 0">No courses available yet.</p>
</div>

<script>
function courseCatalogue() {
    return {
        loading: true,
        courses: [],

        async load() {
            const res = await fetch('/api/v1/courses', { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            this.courses = json.data;
            this.loading = false;
        },

        async publish(course) {
            await fetch(`/api/v1/courses/${course.id}/publish`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            });
            this.load();
        },
    };
}
</script>
@endsection
