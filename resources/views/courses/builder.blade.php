@extends('layouts.app')

@section('title', 'Course Builder - CybCademy')

{{--
    Phase 6 Section 1 explicitly flagged this screen ("Course Builder...
    drag-drop content authoring") as needing genuine interactivity beyond
    Bootstrap/Alpine basics. Drag-drop reordering here uses the native
    HTML5 Drag and Drop API wired through Alpine, rather than pulling in
    a dedicated JS library (Sortable.js etc.) - deliberate: the confirmed
    frontend stack (Phase 4) is Blade + Bootstrap 5 + Alpine.js, and
    native drag events cover this screen's actual need (reorder a flat
    list) without adding a new dependency for one screen.
--}}

@section('content')
<div x-data="courseBuilder({{ $courseId }})" x-init="load()">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="cyb-display mb-0" x-text="course.title || 'Loading…'"></h1>
            <span class="cyb-badge mt-1" :class="course.status === 'published' ? 'cyb-badge--low' : 'cyb-badge--medium'" x-text="course.status"></span>
        </div>
        <button class="btn" style="background-color: var(--cyb-accent); color: #fff;"
                x-show="course.status === 'draft'" @click="publish()" :disabled="lessons.length === 0">
            Publish course
        </button>
    </div>

    <p class="text-muted small" x-show="course.status === 'draft' && lessons.length === 0">
        Add at least one lesson before this course can be published.
    </p>

    {{-- Lesson list - drag to reorder. Each drag re-sends the full
         sequence to the backend on drop, which is the simplest correct
         approach for a flat list of this size (a handful to dozens of
         lessons per course, not thousands) rather than optimistic
         reordering with per-item PATCH calls. --}}
    <div class="cyb-card mb-4">
        <h2 class="h5 cyb-display mb-3">Lessons</h2>

        <ul class="list-group mb-3" x-show="lessons.length > 0">
            <template x-for="(lesson, index) in lessons" :key="lesson.id">
                <li class="list-group-item d-flex align-items-center gap-3"
                    draggable="true"
                    @dragstart="dragIndex = index"
                    @dragover.prevent
                    @drop="reorder(dragIndex, index)"
                    style="cursor: grab;">
                    <i class="bi bi-grip-vertical text-muted" aria-hidden="true"></i>
                    <span class="cyb-mono small text-muted" x-text="index + 1"></span>
                    <div class="flex-grow-1">
                        <div x-text="lesson.title"></div>
                        <span class="small text-muted" x-text="lesson.content_type"></span>
                    </div>
                    <button class="btn btn-sm btn-link text-danger" @click="removeLesson(lesson)" aria-label="Remove lesson">
                        <i class="bi bi-trash"></i>
                    </button>
                </li>
            </template>
        </ul>
        <p class="text-muted small" x-show="lessons.length === 0">No lessons yet - add one below.</p>

        {{-- Add lesson form --}}
        <form @submit.prevent="addLesson()" class="border-top pt-3 mt-3">
            <div class="row g-2">
                <div class="col-md-5">
                    <input type="text" class="form-control form-control-sm" placeholder="Lesson title" x-model="newLesson.title" required>
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" x-model="newLesson.content_type" required>
                        <option value="text">Text</option>
                        <option value="video">Video</option>
                        <option value="interactive">Interactive</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-sm btn-outline-secondary w-100">Add lesson</button>
                </div>
            </div>
            <textarea x-show="newLesson.content_type === 'text'" class="form-control form-control-sm mt-2"
                      rows="3" placeholder="Lesson content" x-model="newLesson.content_body"></textarea>
            <input x-show="newLesson.content_type !== 'text'" type="url" class="form-control form-control-sm mt-2"
                   placeholder="Content URL" x-model="newLesson.content_url">
        </form>
    </div>

    {{-- AI Quiz Generator - Epic E9, tier-gated server-side (402 handled below) --}}
    <div class="cyb-card">
        <h2 class="h5 cyb-display mb-2">Generate quiz questions with AI</h2>
        <p class="small text-muted">Drafts only - review and attach to an assessment before use. Nothing generated here is visible to employees automatically.</p>
        <button class="btn btn-sm btn-outline-secondary" @click="generateQuiz()" :disabled="generatingQuiz || lessons.filter(l => l.content_type === 'text').length === 0">
            <span x-text="generatingQuiz ? 'Generating…' : 'Generate 5 draft questions'"></span>
        </button>
        <p class="small text-danger mt-2" x-show="quizError" x-text="quizError"></p>
        <ul class="mt-3 mb-0" x-show="draftQuestions.length > 0">
            <template x-for="q in draftQuestions" :key="q.id">
                <li x-text="q.question_text"></li>
            </template>
        </ul>
    </div>
</div>

<script>
function courseBuilder(courseId) {
    return {
        course: {},
        lessons: [],
        newLesson: { title: '', content_type: 'text', content_body: '', content_url: '' },
        dragIndex: null,
        generatingQuiz: false,
        quizError: null,
        draftQuestions: [],

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]').content;
        },

        async load() {
            const res = await fetch(`/api/v1/courses/${courseId}`, { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            this.course = json.data;
            this.lessons = json.data.lessons || [];
        },

        async addLesson() {
            const res = await fetch(`/api/v1/courses/${courseId}/lessons`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': this.csrfToken(), 'Content-Type': 'application/json' },
                body: JSON.stringify(this.newLesson),
            });
            if (res.ok) {
                const json = await res.json();
                this.lessons.push(json.data);
                this.newLesson = { title: '', content_type: 'text', content_body: '', content_url: '' };
            }
        },

        async removeLesson(lesson) {
            await fetch(`/api/v1/lessons/${lesson.id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': this.csrfToken() },
            });
            this.lessons = this.lessons.filter(l => l.id !== lesson.id);
        },

        async reorder(fromIndex, toIndex) {
            const moved = this.lessons.splice(fromIndex, 1)[0];
            this.lessons.splice(toIndex, 0, moved);

            await fetch(`/api/v1/courses/${courseId}/lessons/reorder`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': this.csrfToken(), 'Content-Type': 'application/json' },
                body: JSON.stringify({ lesson_ids: this.lessons.map(l => l.id) }),
            });
        },

        async publish() {
            await fetch(`/api/v1/courses/${courseId}/publish`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': this.csrfToken() },
            });
            this.load();
        },

        async generateQuiz() {
            this.generatingQuiz = true;
            this.quizError = null;
            try {
                const res = await fetch(`/api/v1/ai/courses/${courseId}/generate-quiz`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrfToken(), 'Content-Type': 'application/json' },
                    body: JSON.stringify({ question_count: 5 }),
                });
                if (res.status === 402) {
                    this.quizError = 'AI quiz generation requires the Professional plan or above.';
                    return;
                }
                const json = await res.json();
                if (!res.ok) {
                    this.quizError = json.errors?.[0]?.message || 'Could not generate questions.';
                    return;
                }
                this.draftQuestions = json.data.questions;
            } finally {
                this.generatingQuiz = false;
            }
        },
    };
}
</script>
@endsection
