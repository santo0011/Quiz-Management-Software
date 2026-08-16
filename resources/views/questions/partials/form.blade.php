@php($prefix = $prefix ?? 'admin')
@php($optionValues = old('options', $question->exists ? $question->options->pluck('option_text')->all() : ['', '', '', '']))
@php($correctIndex = old('correct_option', $question->exists ? max(0, $question->options->values()->search(fn ($option) => $option->is_correct)) : 0))

<form method="POST" action="{{ $action }}" class="admin-form question-form" data-question-form>
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    {{-- Question Card --}}
    <section class="question-card">
        <div class="question-card-header">
            <span class="question-card-icon"><i class="bi bi-patch-question-fill"></i></span>
            <div>
                <h3>Question Details</h3>
                <p>Enter the question text and configure scoring.</p>
            </div>
        </div>
        <div class="question-card-body">
            <div class="mb-3">
                <label for="question_text" class="form-label">Question Text <span class="required-mark">*</span></label>
                <textarea id="question_text" name="question_text" rows="5" class="form-control form-control-lg question-textarea @error('question_text') is-invalid @enderror math-input" required>{{ old('question_text', $question->question_text) }}</textarea>
                <div class="form-text">Use MathJax syntax like <code>\sqrt{25}</code>, <code>x^2 + 2x + 1</code>, or <code>\frac{a}{b}</code>.</div>
                @error('question_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="question_type" class="form-label">Question Type</label>
                    <select id="question_type" name="question_type" class="form-select form-control">
                        <option value="mcq" @selected(old('question_type', $question->question_type ?? 'mcq') === 'mcq')>MCQ</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="marks" class="form-label">Marks <span class="required-mark">*</span></label>
                    <input id="marks" type="number" step="0.01" min="0.01" name="marks" value="{{ old('marks', $question->marks ?? 1) }}" class="form-control @error('marks') is-invalid @enderror" required>
                    @error('marks')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </section>

    {{-- Options Card --}}
    <section class="question-card">
        <div class="question-card-header">
            <span class="question-card-icon accent"><i class="bi bi-ui-checks"></i></span>
            <div>
                <h3>Answer Options</h3>
                <p>Select the correct answer by clicking the radio button.</p>
            </div>
        </div>
        <div class="question-card-body">
            @error('options')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
            @error('correct_option')<div class="text-danger small mb-2">{{ $message }}</div>@enderror

            <div data-option-list>
                @foreach ($optionValues as $index => $optionValue)
                    <div class="option-row" data-option-row>
                        <label class="option-badge">{{ chr(65 + $index) }}</label>
                        <div class="option-input-wrap">
                            <textarea name="options[]" rows="1" class="form-control math-input" placeholder="Option {{ chr(65 + $index) }} text" required>{{ $optionValue }}</textarea>
                        </div>
                        <label class="correct-answer-label" title="Mark as correct">
                            <input type="radio" name="correct_option" value="{{ $index }}" class="form-check-input correct-radio" @checked((int) $correctIndex === $index) aria-label="Correct option">
                            <span class="correct-answer-mark"><i class="bi bi-check-lg"></i></span>
                        </label>
                        <button type="button" class="btn btn-sm btn-icon-remove" data-remove-option aria-label="Remove option">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                @endforeach
            </div>

            <button type="button" class="btn btn-add-option" data-add-option>
                <i class="bi bi-plus-circle-fill"></i>
                Add Option
            </button>
        </div>
    </section>

    {{-- Explanation Card --}}
    <section class="question-card">
        <div class="question-card-header">
            <span class="question-card-icon success"><i class="bi bi-lightbulb-fill"></i></span>
            <div>
                <h3>Explanation</h3>
                <p>Optional explanation shown after the exam.</p>
            </div>
        </div>
        <div class="question-card-body">
            <label for="explanation" class="form-label">Explanation <span class="text-muted fw-normal">(optional)</span></label>
            <textarea id="explanation" name="explanation" rows="3" class="form-control math-input">{{ old('explanation', $question->explanation) }}</textarea>
        </div>
    </section>

    <div class="question-form-actions">
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
            const LETTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

            const renumberOptions = () => {
                list.querySelectorAll('[data-option-row]').forEach((row, index) => {
                    const badge = row.querySelector('.option-badge');
                    const input = row.querySelector('textarea');
                    badge.textContent = LETTERS[index % 26];
                    input.placeholder = 'Option ' + LETTERS[index % 26];
                    row.querySelector('input[type="radio"]').value = index;
                });
            };

            form.querySelector('[data-add-option]')?.addEventListener('click', () => {
                const count = list.querySelectorAll('[data-option-row]').length;
                const letter = LETTERS[count % 26];
                const row = document.createElement('div');
                row.className = 'option-row';
                row.setAttribute('data-option-row', '');
                row.innerHTML = `
                    <label class="option-badge">${letter}</label>
                    <div class="option-input-wrap">
                        <textarea name="options[]" rows="1" class="form-control math-input" placeholder="Option ${letter}" required></textarea>
                    </div>
                    <label class="correct-answer-label" title="Mark as correct">
                        <input type="radio" name="correct_option" value="${count}" class="form-check-input correct-radio" aria-label="Correct option">
                        <span class="correct-answer-mark"><i class="bi bi-check-lg"></i></span>
                    </label>
                    <button type="button" class="btn btn-sm btn-icon-remove" data-remove-option aria-label="Remove option">
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
