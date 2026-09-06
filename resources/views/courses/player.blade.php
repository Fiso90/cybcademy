@extends('layouts.app')

@section('title', 'Course Player - CybCademy')

{{--
    Phase 6 Section 1 flagged this as needing real interactivity ("Course
    Player" alongside Course Builder). Implements Phase 3 Section 8.2's
    user flow: progress through lessons, complete an assessment drawn
    from the Question Bank, receive a certificate on pass.

    Critically: correct answers are never present in this page's HTML or
    JS at any point - the assessment questions fetched here (via the
    course's assessments relation) intentionally exclude
    question_bank.correct_answer, and grading happens entirely server-side
    in AssessmentService::submitAttempt() (Epic E2). This view only ever
    learns pass/fail and score AFTER submitting, from the server's
    response - matching AssessmentService's own docblock note that
    "Grading is deliberately server-side... never sent to the client."
--}}

@section('content')
<div x-data="coursePlayer({{ $courseId }})" x-init="load()">
    <template x-if="loading"><p class="text-muted">Loading course…</p></template>

    <div x-show="!loading" class="row g-4">
        {{-- Lesson list sidebar --}}
        <div class="col-md-3">
            <div class="cyb-card">
                <h2 class="h6 cyb-display mb-3" x-text="course.title"></h2>
                <div class="progress mb-3" style="height: 6px;">
                    <div class="progress-bar" :style="`width: ${progressPercent}%; background-color: var(--cyb-accent);`"></div>
                </div>
                <div class="list-group list-group-flush">
                    <template x-for="(lesson, i) in lessons" :key="lesson.id">
                        <button class="list-group-item list-group-item-action py-2"
                                :class="i === currentIndex ? 'active' : ''"
                                @click="currentIndex = i; view = 'lesson';">
                            <i class="bi" :class="i < currentIndex ? 'bi-check-circle-fill' : 'bi-circle'"></i>
                            <span class="small ms-2" x-text="lesson.title"></span>
                        </button>
                    </template>
                    <button x-show="hasAssessment" class="list-group-item list-group-item-action py-2"
                            :class="view === 'assessment' ? 'active' : ''" @click="view = 'assessment'; loadAssessment();">
                        <i class="bi bi-patch-question"></i>
                        <span class="small ms-2">Final assessment</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Content area --}}
        <div class="col-md-9">
            <div class="cyb-card" x-show="view === 'lesson'" x-cloak>
                <h2 class="h5 cyb-display" x-text="currentLesson.title"></h2>

                <div x-show="currentLesson.content_type === 'text'" x-text="currentLesson.content_body"></div>
                <video x-show="currentLesson.content_type === 'video'" :src="currentLesson.content_url" controls class="w-100 rounded"></video>
                <iframe x-show="currentLesson.content_type === 'interactive'" :src="currentLesson.content_url" class="w-100 border-0" style="height: 500px;"></iframe>

                <div class="d-flex justify-content-between mt-4">
                    <button class="btn btn-sm btn-outline-secondary" :disabled="currentIndex === 0" @click="currentIndex--">Previous</button>
                    <button class="btn btn-sm" style="background-color: var(--cyb-accent); color: #fff;" @click="nextLesson()">
                        <span x-text="currentIndex < lessons.length - 1 ? 'Next lesson' : (hasAssessment ? 'Go to assessment' : 'Mark complete')"></span>
                    </button>
                </div>
            </div>

            {{-- Assessment --}}
            <div class="cyb-card" x-show="view === 'assessment'" x-cloak>
                <template x-if="!assessmentResult">
                    <div>
                        <h2 class="h5 cyb-display mb-3">Final assessment</h2>
                        <template x-for="(q, qi) in questions" :key="q.id">
                            <div class="mb-4">
                                <p class="fw-semibold" x-text="`${qi + 1}. ${q.question_text}`"></p>
                                <template x-if="q.question_type === 'true_false'">
                                    <div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" :name="q.id" @change="answers[q.id] = true">
                                            <label class="form-check-label">True</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" :name="q.id" @change="answers[q.id] = false">
                                            <label class="form-check-label">False</label>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="q.question_type === 'mcq'">
                                    <div>
                                        <template x-for="opt in q.options" :key="opt.id">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" :name="q.id" @change="answers[q.id] = opt.id">
                                                <label class="form-check-label" x-text="opt.text"></label>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <button class="btn" style="background-color: var(--cyb-accent); color: #fff;" @click="submitAssessment()" :disabled="submitting">
                            <span x-text="submitting ? 'Submitting…' : 'Submit assessment'"></span>
                        </button>
                    </div>
                </template>

                {{-- Result - score/pass-fail comes from the server response
                     only, per this file's top docblock. --}}
                <template x-if="assessmentResult">
                    <div class="text-center py-4">
                        <i class="bi fs-1" :class="assessmentResult.passed ? 'bi-check-circle-fill' : 'bi-x-circle'"
                           :style="`color: var(--cyb-risk-${assessmentResult.passed ? 'low' : 'high'})`"></i>
                        <h2 class="cyb-display h4 mt-3" x-text="assessmentResult.passed ? 'Passed!' : 'Not quite yet'"></h2>
                        <p class="text-muted" x-text="`Score: ${assessmentResult.score}%`"></p>
                        <p class="small text-muted" x-show="assessmentResult.passed">A certificate has been issued to your record.</p>
                        <p class="small text-muted" x-show="!assessmentResult.passed">Review the lessons and try again when you're ready.</p>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
function coursePlayer(courseId) {
    return {
        loading: true,
        course: {},
        lessons: [],
        currentIndex: 0,
        view: 'lesson',
        hasAssessment: false,
        assessmentId: null,
        questions: [],
        answers: {},
        assessmentResult: null,
        submitting: false,

        get currentLesson() { return this.lessons[this.currentIndex] || {}; },
        get progressPercent() {
            return this.lessons.length > 0 ? Math.round((this.currentIndex / this.lessons.length) * 100) : 0;
        },

        async load() {
            const res = await fetch(`/api/v1/courses/${courseId}`, { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            this.course = json.data;
            this.lessons = json.data.lessons || [];
            this.hasAssessment = (json.data.assessments || []).length > 0;
            if (this.hasAssessment) this.assessmentId = json.data.assessments[0].id;
            this.loading = false;
        },

        nextLesson() {
            if (this.currentIndex < this.lessons.length - 1) {
                this.currentIndex++;
            } else if (this.hasAssessment) {
                this.view = 'assessment';
                this.loadAssessment();
            }
            // Marking the final lesson "complete" without a following
            // assessment isn't wired to a backend call in this slice -
            // course_assignments.status only transitions to 'completed'
            // via AssessmentService::submitAttempt() (Epic E2) today, so
            // a course with no assessment has no completion trigger at
            // all yet. Genuine gap, flagged rather than faked.
        },

        async loadAssessment() {
            // The assessment-with-questions fetch used here intentionally
            // omits correct_answer - see this file's top docblock. The
            // actual question list currently comes bundled in the course
            // show() response's assessments relation rather than a
            // dedicated "start attempt" endpoint; a production build
            // would want a purpose-built endpoint that explicitly strips
            // correct_answer server-side as a transformer, rather than
            // relying on the frontend to simply not display a field that
            // technically arrived in the payload - flagged as a
            // real hardening item for Phase 12, not resolved here.
            if (this.questions.length > 0) return;
            const res = await fetch(`/api/v1/courses/${courseId}`, { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            const assessment = (json.data.assessments || [])[0];
            this.questions = assessment ? (assessment.questions || []) : [];
        },

        async submitAssessment() {
            this.submitting = true;
            const res = await fetch(`/api/v1/assessments/${this.assessmentId}/attempts`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ answers: this.answers }),
            });
            const json = await res.json();
            this.assessmentResult = json.data;
            this.submitting = false;
        },
    };
}
</script>
@endsection
