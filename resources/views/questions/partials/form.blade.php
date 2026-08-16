@php($prefix = $prefix ?? 'admin')
@php($optionValues = old('options', $question->exists ? $question->options->pluck('option_text')->all() : ['', '', '', '']))
@php($correctIndex = old('correct_option', $question->exists ? max(0, $question->options->values()->search(fn ($option) => $option->is_correct)) : 0))

<form method="POST" action="{{ $action }}" class="admin-form question-form" data-question-form>
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <div class="mb-3">
        <label for="question_text" class="form-label">Question Text <span class="required-mark">*</span></label>
        <textarea id="question_text" name="question_text" rows="5" class="form-control @error('question_text') is-invalid @enderror math-input" required>{{ old('question_text', $question->question_text) }}</textarea>
        <div class="form-text">Use MathJax syntax like <code>\sqrt{25}</code>, <code>x^2 + 2x + 1</code>, or <code>\frac{a}{b}</code>.</div>
        @error('question_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <label for="question_type" class="form-label">Question Type</label>
            <select id="question_type" name="question_type" class="form-select form-control">
                <option value="mcq" @selected(old('question_type', $question->question_type ?? 'mcq') === 'mcq')>MCQ</option>
            </select>
        </div>
        <div class="col-md-4">
            <label for="marks" class="form-label">Marks <span class="required-mark">*</span></label>
            <input id="marks" type="number" step="0.01" min="0.01" name="marks" value="{{ old('marks', $question->marks ?? 1) }}" class="form-control @error('marks') is-invalid @enderror" required>
            @error('marks')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="option-builder mt-4">
        <div class="d-flex align-items-center justify-content-between gap-3 mb-2">
            <label class="form-label mb-0">Answer Options <span class="required-mark">*</span></label>
            <button type="button" class="btn btn-sm btn-soft" data-add-option>
                <i class="bi bi-plus-circle-fill"></i>
                Add Option
            </button>
        </div>
        @error('options')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
        @error('correct_option')<div class="text-danger small mb-2">{{ $message }}</div>@enderror

        <div data-option-list>
            @foreach ($optionValues as $index => $optionValue)
                <div class="option-row" data-option-row>
                    <input type="radio" name="correct_option" value="{{ $index }}" class="form-check-input" @checked((int) $correctIndex === $index) aria-label="Correct option">
                    <textarea name="options[]" rows="2" class="form-control math-input" required>{{ $optionValue }}</textarea>
                    <button type="button" class="btn btn-sm btn-danger-soft" data-remove-option aria-label="Remove option">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mt-3">
        <label for="explanation" class="form-label">Explanation</label>
        <textarea id="explanation" name="explanation" rows="3" class="form-control math-input">{{ old('explanation', $question->explanation) }}</textarea>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button class="btn btn-primary" type="submit">
            <i class="bi bi-check-circle-fill"></i>
            {{ $button }}
        </button>
        <a href="{{ route($prefix.'.exams.show', $exam) }}" class="btn btn-soft">Cancel</a>
    </div>
</form>

@push('scripts')
    <script>
        document.querySelectorAll('[data-question-form]').forEach((form) => {
            const list = form.querySelector('[data-option-list]');
            const renumberOptions = () => {
                list.querySelectorAll('[data-option-row]').forEach((row, index) => {
                    row.querySelector('input[type="radio"]').value = index;
                });
            };

            form.querySelector('[data-add-option]')?.addEventListener('click', () => {
                const row = document.createElement('div');
                row.className = 'option-row';
                row.setAttribute('data-option-row', '');
                row.innerHTML = `
                    <input type="radio" name="correct_option" value="0" class="form-check-input" aria-label="Correct option">
                    <textarea name="options[]" rows="2" class="form-control math-input" required></textarea>
                    <button type="button" class="btn btn-sm btn-danger-soft" data-remove-option aria-label="Remove option">
                        <i class="bi bi-x-lg"></i>
                    </button>
                `;
                list.appendChild(row);
                renumberOptions();
                row.querySelector('textarea').focus();
            });

            list.addEventListener('click', (event) => {
                const button = event.target.closest('[data-remove-option]');
                if (! button || list.querySelectorAll('[data-option-row]').length <= 2) {
                    return;
                }
                button.closest('[data-option-row]').remove();
                renumberOptions();
            });
        });
    </script>
@endpush
