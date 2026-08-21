@extends('layouts.admin')

@section('title', 'Add Passage Questions')
@section('page-title', 'Add Passage Questions')

@section('content')
    <section class="content-panel">
        <div class="panel-header">
            <div>
                <h2>{{ $exam->title }}</h2>
                <p>Add MCQ questions under this passage/summary.</p>
            </div>
            <a href="{{ route('admin.passage-groups.edit', $passageGroup) }}" class="btn btn-soft">
                <i class="bi bi-pencil-fill"></i>
                Edit Passage
            </a>
        </div>

        <section class="question-card mb-4">
            <div class="question-card-header">
                <span class="question-card-icon accent"><i class="bi bi-file-earmark-text-fill"></i></span>
                <div>
                    <h3>{{ $passageGroup->title }}</h3>
                    <p>Passage/Summary shown to students above these questions.</p>
                </div>
            </div>
            <div class="question-card-body">
                <div class="math-content">{{ $passageGroup->content }}</div>
            </div>
        </section>

        @if ($categories->isEmpty())
            <div class="feedback-alert mb-0">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div>
                    <strong>A question category is required before adding questions.</strong>
                    <p class="mb-2">Create at least one category on the Question Category page, then come back here to add questions.</p>
                    <a href="{{ route('admin.question-categories.index', ['branch_id' => $exam->branch_id]) }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-tags-fill"></i>
                        Go to Question Categories
                    </a>
                </div>
            </div>
        @else
            @include('questions.partials.multi-form', [
                'prefix' => 'admin',
                'action' => route('admin.passage-groups.questions.store', [$exam, $passageGroup]),
                'button' => 'Save Questions',
                'defaultMarks' => $exam->marks_per_question ?? 1,
                'existingQuestions' => $passageGroup->questions,
                'categories' => $categories,
            ])
        @endif
    </section>
@endsection
