@extends('layouts.branch')

@section('title', 'Question Management')
@section('page-title', 'Question Management')

@section('content')
    @php($openAddQuestion = old('_form_key') === 'main')
    @php($openAddSummary = $errors->has('content') || old('content') !== null)

    <section class="content-panel exam-summary-bar">
        <div class="exam-summary-row">
            <div class="exam-summary-title">
                <h2>{{ $exam->title }}</h2>
                <p>Manage questions for {{ $exam->schoolClass?->name }}. Each question has its own category.</p>
            </div>

            @if ($categories->isNotEmpty())
                <div class="default-category-picker" id="defaultCategoryPicker">
                    <label for="defaultCategorySelect" class="default-category-label">
                        <i class="bi bi-tag-fill"></i>
                        Default category for new questions
                    </label>
                    <div class="default-category-controls">
                        <select id="defaultCategorySelect" class="form-select form-control">
                            <option value="">Select category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <button type="button" id="confirmDefaultCategory" class="btn btn-sm btn-primary" disabled>
                            <i class="bi bi-check-circle-fill"></i>
                            Confirm
                        </button>
                    </div>
                    <div class="default-category-confirmed d-none" id="defaultCategoryConfirmed">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Default set to <strong id="defaultCategoryName"></strong> — new questions will use this automatically.</span>
                    </div>
                </div>
            @else
                <div class="exam-summary-category">
                    <span class="category-status-pill warning">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        No categories yet
                    </span>
                    <a href="{{ route('branch.question-categories.index') }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-tags-fill"></i>
                        Create Category
                    </a>
                </div>
            @endif
        </div>
    </section>

    @if ($exam->hasBeenAttempted())
        <div class="feedback-alert info mb-4">
            <i class="bi bi-info-circle-fill"></i>
            <div>
                <strong>A student has already attended this exam.</strong>
                <p class="mb-0">You can still add, edit, and reorder questions below. Existing results will not be affected.</p>
            </div>
        </div>
    @endif

    @if ($categories->isNotEmpty())
        <section class="content-panel add-item-panel" id="add-question-section">
            <div class="panel-header">
                <div>
                    <h2><i class="bi bi-plus-circle-fill me-2 text-primary"></i>Add New Item</h2>
                    <p>Add as many questions and summaries as you need, in any order.</p>
                </div>
            </div>

            <div class="add-item-tiles">
                <button type="button" class="add-item-tile" data-bs-toggle="collapse" data-bs-target="#addQuestionForm">
                    <span class="add-item-tile-icon"><i class="bi bi-patch-question-fill"></i></span>
                    <span class="add-item-tile-body">
                        <strong>Add Another Question</strong>
                        <small>A single MCQ with 4 options, correct answer &amp; marks.</small>
                    </span>
                </button>
                <button type="button" class="add-item-tile add-item-tile-accent" data-bs-toggle="collapse" data-bs-target="#addSummaryForm">
                    <span class="add-item-tile-icon"><i class="bi bi-file-earmark-text-fill"></i></span>
                    <span class="add-item-tile-body">
                        <strong>Add Summary</strong>
                        <small>A passage/summary with its own set of questions.</small>
                    </span>
                </button>
            </div>

            <div class="collapse {{ $openAddQuestion ? 'show' : '' }}" id="addQuestionForm" data-bs-parent="#add-question-section">
                <div class="add-item-inline-form mt-4">
                    @include('questions.partials.multi-form', [
                        'prefix' => 'branch',
                        'formKey' => 'main',
                        'action' => route('branch.questions.store', $exam),
                        'button' => 'Save Questions',
                        'defaultMarks' => $exam->marks_per_question ?? 1,
                        'existingQuestions' => collect(),
                        'showExistingQuestions' => false,
                        'categories' => $categories,
                    ])
                </div>
            </div>

            <div class="collapse {{ $openAddSummary ? 'show' : '' }}" id="addSummaryForm" data-bs-parent="#add-question-section">
                <div class="add-item-inline-form mt-4">
                    <form method="POST" action="{{ route('branch.passage-groups.store', $exam) }}" class="admin-form">
                        @csrf
                        <div class="mb-3">
                            <label for="content" class="form-label">Summary Content <span class="required-mark">*</span></label>
                            @include('partials.summary-editor', [
                                'mathId' => 'summary_content',
                                'mathName' => 'content',
                                'mathValue' => old('content'),
                                'mathPlaceholder' => 'Enter the passage or summary text students will read...',
                                'mathRows' => 6,
                                'mathRequired' => true,
                                'mathClass' => $errors->has('content') ? 'is-invalid' : '',
                            ])
                            @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-check-circle-fill"></i>
                            Save Summary
                        </button>
                    </form>
                </div>
            </div>
        </section>
    @endif

    @include('exams.partials.questions-panel-header', ['exam' => $exam, 'prefix' => 'branch'])

    @include('exams.partials.questions-panel', ['exam' => $exam, 'prefix' => 'branch', 'categories' => $categories])

    @if ($categories->isNotEmpty())
        @php($hasQuestions = $exam->questions->count() > 0)
        <section class="content-panel submit-panel">
            @if ($hasQuestions)
                <a href="{{ route('branch.exams.show', $exam) }}" class="btn btn-success btn-lg">
                    <i class="bi bi-check2-circle"></i>
                    Done
                </a>
                <p>Finished adding questions and summaries? Head back to the exam to review or publish it.</p>
            @else
                <button type="button" class="btn btn-success btn-lg" disabled>
                    <i class="bi bi-check2-circle"></i>
                    Done
                </button>
                <p class="text-danger">Add at least one question before you can finish this exam.</p>
            @endif
        </section>
    @endif

    @push('scripts')
        @include('questions.partials.default-category-script')
    @endpush
@endsection
