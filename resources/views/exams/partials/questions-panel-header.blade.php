@php($prefix = $prefix ?? 'admin')

<section class="content-panel questions-panel-header">
    <div class="panel-header">
        <div>
            <h2><i class="bi bi-list-check me-2 text-primary"></i>Questions</h2>
            <p>Math expressions render with MathJax in management and student screens.</p>
        </div>
        <span class="question-count-badge">
            <i class="bi bi-file-earmark-text"></i>
            {{ $exam->questions->count() }} {{ Str::plural('Question', $exam->questions->count()) }}
        </span>
    </div>
</section>
