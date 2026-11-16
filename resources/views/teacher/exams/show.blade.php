@extends('layouts.teacher')

@section('title', $exam->title)
@section('page-title', 'Exam Questions')

@php
    $orderedItems = $exam->orderedItems();
    $questionNumbers = $exam->questionNumbers($orderedItems);
@endphp

@section('content')
    <div class="student-profile-top-actions">
        <a href="{{ route('teacher.exams.index') }}" class="btn btn-outline-secondary btn-student-back">
            <i class="bi bi-arrow-left"></i>
            Back
        </a>
    </div>

    <section class="exam-hero">
        <div class="exam-hero-overlay"></div>
        <div class="exam-hero-content">
            <div class="exam-hero-top">
                <div class="exam-hero-badges">
                    <span class="exam-status-badge status-{{ $exam->status }}">{{ ucfirst($exam->status) }}</span>
                    <span class="exam-meta-chip">
                        <i class="bi {{ $exam->isGlobal() ? 'bi-globe2' : 'bi-building' }}"></i>
                        {{ $exam->isGlobal() ? 'All branches' : 'This branch' }}
                    </span>
                    @if ($exam->gradeNames() !== '')
                        <span class="exam-meta-chip"><i class="bi bi-people-fill"></i> {{ $exam->gradeNames() }}</span>
                    @endif
                    @if ($exam->subject)
                        <span class="exam-meta-chip"><i class="bi bi-book-fill"></i> {{ $exam->subject->name }}</span>
                    @endif
                </div>
                <div class="exam-hero-actions">
                    <span class="exam-published-lock"><i class="bi bi-eye-fill"></i> View only</span>
                </div>
            </div>
            <h1 class="exam-hero-title">{{ $exam->title }}</h1>
            @if ($exam->description)
                <p class="exam-hero-desc">{{ $exam->description }}</p>
            @endif
        </div>
    </section>

    <section class="exam-stats-grid">
        <div class="exam-stat-card">
            <div class="exam-stat-icon primary"><i class="bi bi-trophy-fill"></i></div>
            <div class="exam-stat-body"><span>Total Marks</span><strong>{{ $exam->total_marks }}</strong></div>
        </div>
        <div class="exam-stat-card">
            <div class="exam-stat-icon warning"><i class="bi bi-stopwatch-fill"></i></div>
            <div class="exam-stat-body"><span>Duration</span><strong>{{ $exam->duration_minutes }} <small>min</small></strong></div>
        </div>
        <div class="exam-stat-card">
            <div class="exam-stat-icon info"><i class="bi bi-question-circle-fill"></i></div>
            <div class="exam-stat-body"><span>Questions</span><strong>{{ $exam->questions->count() }}</strong></div>
        </div>
    </section>

    <section class="content-panel questions-panel question-mgmt-panel">
        <div class="panel-header">
            <div>
                <h2><i class="bi bi-list-check me-2 text-primary"></i>Questions</h2>
                <p>{{ $exam->questions->count() }} {{ Str::plural('question', $exam->questions->count()) }} in this exam, with the correct answers and explanations.</p>
            </div>
        </div>

        @if ($orderedItems->isEmpty())
            <div class="empty-state">
                <i class="bi bi-patch-question"></i>
                <h3>No questions yet</h3>
            </div>
        @else
            <div class="question-list">
                @foreach ($orderedItems as $item)
                    @if ($item['type'] === 'question')
                        @include('teacher.exams.partials.question', ['question' => $item['question'], 'number' => $questionNumbers[$item['question']->id] ?? $loop->iteration])
                    @else
                        @php($group = $item['group'])
                        <article class="question-admin-item passage-group-item">
                            <div class="question-item-header">
                                <div class="question-number-badge"><i class="bi bi-file-earmark-text-fill"></i></div>
                                <div class="question-item-content">
                                    <div class="question-item-meta">
                                        <span class="question-marks-badge">
                                            <i class="bi bi-collection"></i>
                                            Passage/Summary · {{ $group->questions->count() }} {{ Str::plural('question', $group->questions->count()) }}
                                        </span>
                                    </div>
                                    <h3 class="question-item-text">{{ $group->title }}</h3>
                                </div>
                            </div>

                            @if ($group->image_path)
                                <img src="{{ Storage::disk('public')->url($group->image_path) }}" alt="{{ $group->title }}" class="img-fluid rounded mb-3" style="max-height: 320px;">
                            @endif
                            <div class="passage-preview math-content">{!! \App\Support\HtmlSanitizer::sanitize($group->content) !!}</div>

                            <div class="passage-group-questions">
                                @foreach ($group->questions as $question)
                                    @include('teacher.exams.partials.question', ['question' => $question, 'number' => $questionNumbers[$question->id] ?? $loop->iteration])
                                @endforeach
                            </div>
                        </article>
                    @endif
                @endforeach
            </div>
        @endif
    </section>
@endsection

@push('scripts')
    <script>
        window.MathJax = { tex: { inlineMath: [['$', '$'], ['\\(', '\\)']] } };
    </script>
    <script async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
@endpush
