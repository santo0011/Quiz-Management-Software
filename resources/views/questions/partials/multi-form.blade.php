@php($prefix = $prefix ?? 'admin')
@php($oldQuestions = old('questions', [[]]))

<form method="POST" action="{{ $action }}" class="admin-form question-form" data-multi-question-form data-default-marks="{{ $defaultMarks ?? 1 }}">
    @csrf

    <div data-multi-question-list>
        @foreach ($oldQuestions as $qIndex => $oldQ)
            @php($qIndex = (int) $qIndex)
            @php($qOptions = $oldQ['options'] ?? ['', '', '', ''])
            @php($qCorrect = (int) ($oldQ['correct_option'] ?? 0))
            <section class="question-card multi-question-block" data-question-block>
                <div class="question-card-header">
                    <span class="question-card-icon"><i class="bi bi-patch-question-fill"></i></span>
                    <div class="flex-grow-1">
                        <h3 data-question-number>Question {{ $qIndex + 1 }}</h3>
                        <p>Enter the question text, options, and select the correct answer.</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-danger-soft" data-remove-question aria-label="Remove question">
                        <i class="bi bi-trash-fill"></i>
                    </button>
                </div>
                <div class="question-card-body">
                    <div class="mb-3">
                        <label class="form-label">Question Text <span class="required-mark">*</span></label>
                        <textarea name="questions[{{ $qIndex }}][question_text]" rows="4" class="form-control question-textarea math-input @error('questions.'.$qIndex.'.question_text') is-invalid @enderror" required>{{ $oldQ['question_text'] ?? '' }}</textarea>
                        @error('questions.'.$qIndex.'.question_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label">Marks <span class="required-mark">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="questions[{{ $qIndex }}][marks]" value="{{ $oldQ['marks'] ?? $defaultMarks }}" class="form-control @error('questions.'.$qIndex.'.marks') is-invalid @enderror" required>
                            @error('questions.'.$qIndex.'.marks')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label">Type</label>
                            <select name="questions[{{ $qIndex }}][question_type]" class="form-select form-control">
                                <option value="mcq">MCQ</option>
                            </select>
                        </div>
                    </div>

                    <div class="option-builder" data-option-builder>
                        <label class="form-label">Answer Options <span class="required-mark">*</span></label>
                        @error('questions.'.$qIndex.'.options')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                        @error('questions.'.$qIndex.'.correct_option')<div class="text-danger small mb-2">{{ $message }}</div>@enderror

                        <div data-option-list>
                            @foreach ($qOptions as $optIndex => $optValue)
                                <div class="option-row" data-option-row>
                                    <label class="option-badge">{{ chr(65 + (int) $optIndex) }}</label>
                                    <div class="option-input-wrap">
                                        <textarea name="questions[{{ $qIndex }}][options][]" rows="1" class="form-control math-input" placeholder="Option {{ chr(65 + (int) $optIndex) }} text" required>{{ $optValue }}</textarea>
                                    </div>
                                    <label class="correct-answer-label" title="Mark as correct">
                                        <input type="radio" name="questions[{{ $qIndex }}][correct_option]" value="{{ (int) $optIndex }}" class="form-check-input correct-radio" @checked($qCorrect === (int) $optIndex) aria-label="Correct option">
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

                    <div class="mt-3">
                        <label class="form-label">Explanation <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea name="questions[{{ $qIndex }}][explanation]" rows="2" class="form-control math-input">{{ $oldQ['explanation'] ?? '' }}</textarea>
                    </div>
                </div>
            </section>
        @endforeach
    </div>

    <div class="d-flex gap-2 mt-3">
        <button type="button" class="btn btn-add-question" data-add-question>
            <i class="bi bi-plus-circle-fill"></i>
            Add Another Question
        </button>
    </div>

    <div class="question-form-actions mt-3">
        <button class="btn btn-primary" type="submit">
            <i class="bi bi-check-circle-fill"></i>
            {{ $button }}
        </button>
        <a href="{{ route($prefix.'.exams.show', $exam) }}" class="btn btn-soft">Cancel</a>
    </div>
</form>

@push('scripts')
    <script>
        document.querySelectorAll('[data-multi-question-form]').forEach((form) => {
            const list = form.querySelector('[data-multi-question-list]');
            const LETTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

            // Re-number blocks and update name indices + badge letters
            const renumberAll = () => {
                list.querySelectorAll('[data-question-block]').forEach((block, qIdx) => {
                    block.querySelector('[data-question-number]').textContent = 'Question ' + (qIdx + 1);
                    const radiosByName = block.querySelectorAll('input[type="radio"]');
                    const headers = ['question_text', 'marks', 'question_type', 'explanation'];
                    headers.forEach((field) => {
                        const el = block.querySelector('[name*="[' + field + ']"]');
                        if (el) {
                            const name = el.name.replace(/questions\[\d+\]\[/g, 'questions[' + qIdx + '][');
                            el.name = name;
                        }
                    });
                    const texts = block.querySelectorAll('textarea');
                    texts.forEach((el) => {
                        const name = el.name.replace(/questions\[\d+\]\[/g, 'questions[' + qIdx + '][');
                        el.name = name;
                    });
                    block.querySelectorAll('[data-option-row]').forEach((row, optIdx) => {
                        const badge = row.querySelector('.option-badge');
                        const textarea = row.querySelector('textarea');
                        const radio = row.querySelector('input[type="radio"]');
                        badge.textContent = LETTERS[optIdx % 26];
                        textarea.placeholder = 'Option ' + LETTERS[optIdx % 26];
                        const textName = textarea.name.replace(/questions\[\d+\]\[options\]\[\]/g, 'questions[' + qIdx + '][options][]');
                        textarea.name = textName;
                        const radioName = radio.name.replace(/questions\[\d+\]\[correct_option\]/g, 'questions[' + qIdx + '][correct_option]');
                        radio.name = radioName;
                        radio.value = optIdx;
                    });
                });
            };

            // Options: add / remove
            const wireOptions = (block) => {
                const optionList = block.querySelector('[data-option-list]');
                const optionBuilder = block.querySelector('[data-option-builder]');
                const blockIndex = () => Array.from(list.querySelectorAll('[data-question-block]')).indexOf(block);

                block.querySelector('[data-add-option]')?.addEventListener('click', () => {
                    const qIdx = blockIndex();
                    const optCount = optionList.querySelectorAll('[data-option-row]').length;
                    const letter = LETTERS[optCount % 26];
                    const row = document.createElement('div');
                    row.className = 'option-row';
                    row.setAttribute('data-option-row', '');
                    row.innerHTML = `
                        <label class="option-badge">${letter}</label>
                        <div class="option-input-wrap">
                            <textarea name="questions[${qIdx}][options][]" rows="1" class="form-control math-input" placeholder="Option ${letter}" required></textarea>
                        </div>
                        <label class="correct-answer-label" title="Mark as correct">
                            <input type="radio" name="questions[${qIdx}][correct_option]" value="${optCount}" class="form-check-input correct-radio" aria-label="Correct option">
                            <span class="correct-answer-mark"><i class="bi bi-check-lg"></i></span>
                        </label>
                        <button type="button" class="btn btn-sm btn-icon-remove" data-remove-option aria-label="Remove option">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    `;
                    optionList.appendChild(row);
                    renumberAll();
                    row.querySelector('textarea').focus();
                });

                optionList.addEventListener('click', (event) => {
                    const button = event.target.closest('[data-remove-option]');
                    if (! button || optionList.querySelectorAll('[data-option-row]').length <= 2) {
                        return;
                    }
                    button.closest('[data-option-row]').remove();
                    renumberAll();
                });
            };

            list.querySelectorAll('[data-question-block]').forEach(wireOptions);

            // Add another question
            form.querySelector('[data-add-question]')?.addEventListener('click', () => {
                const qIdx = list.querySelectorAll('[data-question-block]').length;
                const block = document.createElement('section');
                block.className = 'question-card multi-question-block';
                block.setAttribute('data-question-block', '');
                block.innerHTML = `
                    <div class="question-card-header">
                        <span class="question-card-icon"><i class="bi bi-patch-question-fill"></i></span>
                        <div class="flex-grow-1">
                            <h3 data-question-number>Question ${qIdx + 1}</h3>
                            <p>Enter the question text, options, and select the correct answer.</p>
                        </div>
                        <button type="button" class="btn btn-sm btn-danger-soft" data-remove-question aria-label="Remove question">
                            <i class="bi bi-trash-fill"></i>
                        </button>
                    </div>
                    <div class="question-card-body">
                        <div class="mb-3">
                            <label class="form-label">Question Text <span class="required-mark">*</span></label>
                            <textarea name="questions[${qIdx}][question_text]" rows="4" class="form-control question-textarea math-input" required></textarea>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label">Marks <span class="required-mark">*</span></label>
                                <input type="number" step="0.01" min="0.01" name="questions[${qIdx}][marks]" value="${form.dataset.defaultMarks || '1'}" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Type</label>
                                <select name="questions[${qIdx}][question_type]" class="form-select form-control">
                                    <option value="mcq">MCQ</option>
                                </select>
                            </div>
                        </div>
                        <div class="option-builder" data-option-builder>
                            <label class="form-label">Answer Options <span class="required-mark">*</span></label>
                            <div data-option-list>
                                ${['A', 'B', 'C', 'D'].map((letter, i) => `
                                    <div class="option-row" data-option-row>
                                        <label class="option-badge">${letter}</label>
                                        <div class="option-input-wrap">
                                            <textarea name="questions[${qIdx}][options][]" rows="1" class="form-control math-input" placeholder="Option ${letter}" required></textarea>
                                        </div>
                                        <label class="correct-answer-label" title="Mark as correct">
                                            <input type="radio" name="questions[${qIdx}][correct_option]" value="${i}" class="form-check-input correct-radio" ${i === 0 ? 'checked' : ''} aria-label="Correct option">
                                            <span class="correct-answer-mark"><i class="bi bi-check-lg"></i></span>
                                        </label>
                                        <button type="button" class="btn btn-sm btn-icon-remove" data-remove-option aria-label="Remove option">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                `).join('')}
                            </div>
                            <button type="button" class="btn btn-add-option" data-add-option>
                                <i class="bi bi-plus-circle-fill"></i>
                                Add Option
                            </button>
                        </div>
                        <div class="mt-3">
                            <label class="form-label">Explanation <span class="text-muted fw-normal">(optional)</span></label>
                            <textarea name="questions[${qIdx}][explanation]" rows="2" class="form-control math-input"></textarea>
                        </div>
                    </div>
                `;
                list.appendChild(block);
                wireOptions(block);
                renumberAll();
                block.querySelector('textarea').scrollIntoView({behavior: 'smooth', block: 'center'});
                block.querySelector('textarea').focus();
            });

            // Remove question
            list.addEventListener('click', (event) => {
                const button = event.target.closest('[data-remove-question]');
                if (! button || list.querySelectorAll('[data-question-block]').length <= 1) {
                    return;
                }
                button.closest('[data-question-block]').remove();
                renumberAll();
            });
        });
    </script>
@endpush