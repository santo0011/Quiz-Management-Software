@php($prefix = $prefix ?? 'admin')

<form method="POST" action="{{ $action }}" class="admin-form">
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <section class="question-card">
        <div class="question-card-header">
            <span class="question-card-icon accent"><i class="bi bi-file-earmark-text-fill"></i></span>
            <div>
                <h3>Passage / Summary Details</h3>
                <p>This will be shown once, shared by every question you add underneath it.</p>
            </div>
        </div>
        <div class="question-card-body">
            <div class="mb-1">
                <label for="content" class="form-label">Passage / Summary Content <span class="required-mark">*</span></label>
                @include('partials.summary-editor', [
                    'mathId' => 'content',
                    'mathName' => 'content',
                    'mathValue' => old('content', $passageGroup->content),
                    'mathPlaceholder' => 'Enter the passage or summary text students will read...',
                    'mathRows' => 6,
                    'mathRequired' => true,
                    'mathClass' => $errors->has('content') ? 'is-invalid' : '',
                ])
                @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </section>

    <div class="question-form-actions mt-3">
        <button class="btn btn-primary" type="submit">
            <i class="bi bi-check-circle-fill"></i>
            {{ $button }}
        </button>
        <a href="{{ route($prefix.'.questions.create', $exam) }}" class="btn btn-soft">Cancel</a>
    </div>
</form>
