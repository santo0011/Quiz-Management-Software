@extends('layouts.admin')

@section('title', 'Question Management')
@section('page-title', 'Question Management')

@section('content')
    @php($openAddQuestion = old('_form_key') === 'main')
    @php($openAddSummary = $errors->has('content') || old('content') !== null)

    <section class="content-panel exam-summary-bar">
        <div class="exam-summary-row">
            <div class="exam-summary-title">
                <h2>{{ $exam->title }}</h2>
                <p>Manage questions for {{ $exam->schoolClass?->name }}.</p>
            </div>

            <div class="exam-summary-category">
                @if ($categories->isEmpty())
                    <span class="category-status-pill warning">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        No categories yet
                    </span>
                    <a href="{{ route('admin.question-categories.index', ['branch_id' => $exam->branch_id]) }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-tags-fill"></i>
                        Create Category
                    </a>
                @elseif ($exam->question_category_id)
                    <span class="question-marks-badge">
                        <i class="bi bi-tag"></i>
                        {{ $exam->category->name }}
                    </span>
                    <button type="button" class="btn btn-sm btn-soft" data-bs-toggle="collapse" data-bs-target="#changeCategoryForm">
                        <i class="bi bi-pencil-fill"></i>
                        Change
                    </button>
                @else
                    <span class="category-status-pill danger">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        Category required
                    </span>
                @endif
            </div>
        </div>

        @if ($categories->isEmpty())
            {{-- Nothing more to show: the pill + link above already covers this state. --}}
        @elseif ($exam->question_category_id)
            <div class="collapse mt-3" id="changeCategoryForm">
                <form method="POST" action="{{ route('admin.exams.category.update', $exam) }}" class="row g-2 align-items-end">
                    @csrf
                    @method('PUT')
                    <div class="col-sm-8 col-md-6">
                        <label for="question_category_id" class="form-label">Category <span class="required-mark">*</span></label>
                        <select id="question_category_id" name="question_category_id" class="form-select form-control @error('question_category_id') is-invalid @enderror" required>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected($exam->question_category_id == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('question_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle-fill"></i>
                            Save Category
                        </button>
                    </div>
                </form>
            </div>
        @else
            <form method="POST" action="{{ route('admin.exams.category.update', $exam) }}" class="row g-2 align-items-end mt-3">
                @csrf
                @method('PUT')
                <div class="col-sm-8 col-md-6">
                    <label for="question_category_id" class="form-label">Select a category <span class="required-mark">*</span></label>
                    <select id="question_category_id" name="question_category_id" class="form-select form-control @error('question_category_id') is-invalid @enderror" required>
                        <option value="">Select category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('question_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle-fill"></i>
                        Save Category
                    </button>
                </div>
            </form>
        @endif
    </section>

    @if ($exam->question_category_id)
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

            <div class="collapse {{ $openAddQuestion ? 'show' : '' }}" id="addQuestionForm">
                <div class="add-item-inline-form mt-4">
                    @include('questions.partials.multi-form', [
                        'prefix' => 'admin',
                        'formKey' => 'main',
                        'action' => route('admin.questions.store', $exam),
                        'button' => 'Save Questions',
                        'defaultMarks' => $exam->marks_per_question ?? 1,
                        'existingQuestions' => collect(),
                        'showExistingQuestions' => false,
                    ])
                </div>
            </div>

            <div class="collapse {{ $openAddSummary ? 'show' : '' }}" id="addSummaryForm">
                <div class="add-item-inline-form mt-4">
                    <form method="POST" action="{{ route('admin.passage-groups.store', $exam) }}" class="admin-form">
                        @csrf
                        <div class="mb-3">
                            <label for="content" class="form-label">Summary Content <span class="required-mark">*</span></label>
                            @include('partials.math-editor', [
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

    @include('exams.partials.questions-panel-header', ['exam' => $exam, 'prefix' => 'admin'])

    @include('exams.partials.questions-panel', ['exam' => $exam, 'prefix' => 'admin'])

    @if ($exam->question_category_id)
        @php($hasQuestions = $exam->questions->count() > 0)
        <section class="content-panel submit-panel">
            <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#examSettingsModal" @disabled(!$hasQuestions)>
                <i class="bi bi-check2-circle"></i>
                Submit
            </button>
            @if ($hasQuestions)
                <p>Finished adding questions and summaries? Submit to set Duration &amp; Pass Marks and save the exam.</p>
            @else
                <p class="text-danger">Add at least one question before you can submit this exam.</p>
            @endif
        </section>
    @endif

    @include('exams.partials.settings-modal', ['exam' => $exam, 'prefix' => 'admin'])
@endsection
