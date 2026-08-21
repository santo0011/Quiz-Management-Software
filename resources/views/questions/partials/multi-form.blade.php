@php($prefix = $prefix ?? 'admin')
@php($formKey = $formKey ?? 'main')
@php($oldQuestions = old('_form_key') === $formKey ? old('questions', [[]]) : [[]])
@php($existingQuestions = $existingQuestions ?? collect())
@php($existingCount = $existingQuestions->count())

<form method="POST" action="{{ $action }}" class="admin-form question-form" data-multi-question-form data-form-id="{{ $formKey }}" data-default-marks="{{ (int) ($defaultMarks ?? 1) }}" data-existing-count="{{ $existingCount }}">
    @csrf
    <input type="hidden" name="_form_key" value="{{ $formKey }}">

    @if ($existingQuestions->isNotEmpty() && ($showExistingQuestions ?? true))
        <div class="existing-questions-panel mb-4">
            @foreach ($existingQuestions as $existingQuestion)
                <section class="question-card multi-question-block existing-question-card">
                    <div class="question-card-header">
                        <span class="question-card-icon success"><i class="bi bi-check-circle-fill"></i></span>
                        <div class="flex-grow-1">
                            <h3>Question {{ $loop->iteration }} <span class="badge-saved">Saved</span></h3>
                            <p>This question is already saved to this exam.</p>
                        </div>
                        <a href="{{ route($prefix.'.questions.edit', $existingQuestion) }}" class="btn btn-sm btn-soft" data-bs-toggle="tooltip" data-bs-title="Edit question">
                            <i class="bi bi-pencil-fill"></i>
                        </a>
                    </div>
                    <div class="question-card-body">
                        <div class="question-field-group">
                            <label class="form-label">Question Text</label>
                            @include('partials.math-editor', [
                                'mathId' => 'existing_q' . $existingQuestion->id . '_question_text',
                                'mathValue' => $existingQuestion->question_text,
                                'mathRows' => 2,
                                'mathReadonly' => true,
                            ])
                        </div>

                        <div class="row g-2 question-meta-row">
                            <div class="col-6">
                                <label class="form-label">Marks</label>
                                <input type="number" value="{{ (int) $existingQuestion->marks }}" class="form-control" disabled>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Category</label>
                                <input type="text" value="{{ $existingQuestion->category?->name ?? '—' }}" class="form-control" disabled>
                            </div>
                        </div>

                        <div class="option-builder">
                            <label class="form-label">Answer Options</label>
                            <div data-option-list>
                                @foreach ($existingQuestion->options as $option)
                                    <div class="option-row">
                                        <label class="option-badge">{{ chr(65 + $loop->index) }}</label>
                                        <div class="option-input-wrap">
                                            @include('partials.math-editor', [
                                                'mathId' => 'existing_q' . $existingQuestion->id . '_option_' . $loop->index,
                                                'mathValue' => $option->option_text,
                                                'mathRows' => 1,
                                                'mathReadonly' => true,
                                            ])
                                        </div>
                                        <label class="correct-answer-label" title="Correct answer">
                                            <input type="radio" class="form-check-input correct-radio" @checked($option->is_correct) disabled aria-label="Correct option">
                                            <span class="correct-answer-mark"><i class="bi bi-check-lg"></i></span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        @if ($existingQuestion->explanation)
                            <div class="question-explanation-field">
                                <label class="form-label">Explanation</label>
                                @include('partials.math-editor', [
                                    'mathId' => 'existing_q' . $existingQuestion->id . '_explanation',
                                    'mathValue' => $existingQuestion->explanation,
                                    'mathRows' => 1,
                                    'mathReadonly' => true,
                                ])
                            </div>
                        @endif
                    </div>
                </section>
            @endforeach
        </div>
    @endif

    <div data-multi-question-list>
        @foreach ($oldQuestions as $qIndex => $oldQ)
            @php($qIndex = (int) $qIndex)
            @php($qOptions = $oldQ['options'] ?? ['', '', '', ''])
            @php($qCorrect = (int) ($oldQ['correct_option'] ?? 0))
            <section class="question-card multi-question-block" data-question-block>
                <div class="question-card-header">
                    <span class="question-card-icon"><i class="bi bi-patch-question-fill"></i></span>
                    <div class="flex-grow-1">
                        <h3 data-question-number>Question {{ $existingCount + $qIndex + 1 }}</h3>
                        <p>Enter the question text, options, and select the correct answer.</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-danger-soft" data-remove-question aria-label="Remove question">
                        <i class="bi bi-trash-fill"></i>
                    </button>
                </div>
                <div class="question-card-body">
                    <div class="question-field-group">
                        <label class="form-label">Question Text <span class="required-mark">*</span></label>
                        @include('partials.math-editor', [
                            'mathId' => $formKey . '_q' . $qIndex . '_question_text',
                            'mathName' => 'questions[' . $qIndex . '][question_text]',
                            'mathValue' => $oldQ['question_text'] ?? '',
                            'mathPlaceholder' => 'Enter question text or math expression...',
                            'mathRows' => 2,
                            'mathRequired' => true,
                            'mathClass' => $errors->has('questions.' . $qIndex . '.question_text') ? 'is-invalid' : '',
                        ])
                        @error('questions.'.$qIndex.'.question_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row g-2 question-meta-row">
                        <div class="col-md-6">
                            <label class="form-label">Marks <span class="required-mark">*</span></label>
                            <input type="number" step="1" min="1" name="questions[{{ $qIndex }}][marks]" value="{{ (int) ($oldQ['marks'] ?? $defaultMarks) }}" class="form-control @error('questions.'.$qIndex.'.marks') is-invalid @enderror" required>
                            @error('questions.'.$qIndex.'.marks')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category <span class="required-mark">*</span></label>
                            <select name="questions[{{ $qIndex }}][question_category_id]" class="form-select form-control @error('questions.'.$qIndex.'.question_category_id') is-invalid @enderror" required>
                                <option value="">Select category</option>
                                @foreach ($categories ?? [] as $categoryOption)
                                    <option value="{{ $categoryOption->id }}" @selected(($oldQ['question_category_id'] ?? '') == $categoryOption->id)>{{ $categoryOption->name }}</option>
                                @endforeach
                            </select>
                            @error('questions.'.$qIndex.'.question_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                                        @include('partials.math-editor', [
                                            'mathId' => $formKey . '_q' . $qIndex . '_option_' . $optIndex,
                                            'mathName' => 'questions[' . $qIndex . '][options][]',
                                            'mathValue' => $optValue,
                                            'mathPlaceholder' => 'Option ' . chr(65 + (int) $optIndex) . ' text or math expression',
                                            'mathRows' => 1,
                                            'mathRequired' => true,
                                        ])
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

                    <div class="question-explanation-field">
                        <label class="form-label">Explanation <span class="text-muted fw-normal">(optional)</span></label>
                        @include('partials.math-editor', [
                            'mathId' => $formKey . '_q' . $qIndex . '_explanation',
                            'mathName' => 'questions[' . $qIndex . '][explanation]',
                            'mathValue' => $oldQ['explanation'] ?? '',
                            'mathPlaceholder' => 'Enter explanation text or math expression...',
                            'mathRows' => 1,
                            'mathRequired' => false,
                        ])
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
        <a href="{{ route($prefix.'.questions.create', $exam) }}" class="btn btn-soft">Cancel</a>
    </div>
</form>

@push('scripts')
    @if ($existingQuestions->isNotEmpty())
        <script>
            window.MathJax = { tex: { inlineMath: [['$', '$'], ['\\(', '\\)']] } };
        </script>
        <script async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    @endif
    <script>
        // Only initialize the specific form instance this partial was rendered for.
        // The partial is included multiple times on the same page (main form + one
        // per summary), and each inclusion pushes this script. Without scoping to
        // the form's unique data-form-id, every script would attach listeners to
        // every form, causing "Add Another Question" to create 2-3 cards at once.
        (function () {
        const formId = @json($formKey);
        const CATEGORIES = @json(($categories ?? collect())->map(fn ($categoryOption) => ['id' => $categoryOption->id, 'name' => $categoryOption->name])->values());
        const escapeHtml = (str) => String(str).replace(/[&<>"']/g, (ch) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[ch]));
        const categoryOptionsHtml = () => '<option value="">Select category</option>' + CATEGORIES.map((c) => `<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('');
        const form = document.querySelector('[data-multi-question-form][data-form-id="' + formId + '"]');
        if (form) {
            const list = form.querySelector('[data-multi-question-list]');
            const LETTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            const existingCount = parseInt(form.dataset.existingCount || '0', 10);

            // Re-number blocks and update name indices + badge letters
            const renumberAll = () => {
                list.querySelectorAll('[data-question-block]').forEach((block, qIdx) => {
                    block.querySelector('[data-question-number]').textContent = 'Question ' + (existingCount + qIdx + 1);
                    const headers = ['question_text', 'marks', 'question_category_id', 'explanation'];
                    headers.forEach((field) => {
                        const el = block.querySelector('[name*="[' + field + ']"]');
                        if (el) {
                            const name = el.name.replace(/questions\[\d+\]\[/g, 'questions[' + (existingCount + qIdx) + '][');
                            el.name = name;
                        }
                    });
                    // Update textareas for math fields
                    block.querySelectorAll('[data-math-textarea]').forEach((el) => {
                        const name = el.name.replace(/questions\[\d+\]\[/g, 'questions[' + (existingCount + qIdx) + '][');
                        el.name = name;
                    });
                    block.querySelectorAll('[data-option-row]').forEach((row, optIdx) => {
                        const badge = row.querySelector('.option-badge');
                        const textarea = row.querySelector('[data-math-textarea]');
                        const radio = row.querySelector('input[type="radio"]');
                        badge.textContent = LETTERS[optIdx % 26];
                        if (textarea) {
                            textarea.placeholder = 'Option ' + LETTERS[optIdx % 26] + ' text or math expression';
                        }
                        const radioName = radio.name.replace(/questions\[\d+\]\[correct_option\]/g, 'questions[' + (existingCount + qIdx) + '][correct_option]');
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
                            <div class="math-input-wrap" data-math-input-wrap>
                                <textarea name="questions[${qIdx}][options][]" rows="1" class="form-control" placeholder="Option ${letter} text or math expression" required data-math-textarea></textarea>
                                <button type="button" class="math-toolbar-toggle" data-math-toggle aria-label="Math tools" title="Math tools"><i class="bi bi-123"></i></button>
                                <div class="math-toolbar-popover" data-math-toolbar hidden>
                                    <div class="math-toolbar-header"><span>Math Tools</span><button type="button" class="btn-close btn-close-sm" data-math-close aria-label="Close"></button></div>
                                    <div class="math-toolbar-grid">
                                        <button type="button" class="math-tool-btn" data-math-insert="\\frac{}{}" title="Fraction">a/b</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="^{}" title="Superscript">x²</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="_{}" title="Subscript">x₂</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\sqrt{}" title="Square Root">√</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\pi" title="Pi">π</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\alpha" title="Alpha">α</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\beta" title="Beta">β</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\theta" title="Theta">θ</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\pm" title="±">±</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\times" title="×">×</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\div" title="÷">÷</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\leq" title="≤">≤</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\geq" title="≥">≥</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\neq" title="≠">≠</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\infty" title="∞">∞</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\sum_{}^{}" title="Σ">Σ</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\int_{}^{}" title="∫">∫</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\rightarrow" title="→">→</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\left( \\right)" title="( )">( )</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\left[ \\right]" title="[ ]">[ ]</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\left\\{ \\right\\}" title="{ }">{ }</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\cdot" title="·">·</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\Delta" title="Δ">Δ</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\lambda" title="λ">λ</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\mu" title="μ">μ</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\sigma" title="σ">σ</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\omega" title="ω">ω</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\degree" title="°">°</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\angle" title="∠">∠</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\perp" title="⊥">⊥</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\parallel" title="∥">∥</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\cong" title="≅">≅</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\sim" title="∼">∼</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\in" title="∈">∈</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\notin" title="∉">∉</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\subset" title="⊂">⊂</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\cup" title="∪">∪</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\cap" title="∩">∩</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\emptyset" title="∅">∅</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\therefore" title="∴">∴</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\because" title="∵">∵</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\forall" title="∀">∀</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\exists" title="∃">∃</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\neg" title="¬">¬</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\land" title="∧">∧</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\lor" title="∨">∨</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\implies" title="⇒">⇒</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\iff" title="⇔">⇔</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\overline{}" title="‾">‾</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\overrightarrow{}" title="→">→</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\begin{cases} & \\\\ & \\end{cases}" title="{ }">{ }</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\begin{matrix} & \\\\ & \\end{matrix}" title="[ ]">[ ]</button>
                                    </div>
                                </div>
                            </div>
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
                    const textarea = row.querySelector('[data-math-textarea]');
                    if (textarea) {
                        textarea.focus();
                    }
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
                            <h3 data-question-number>Question ${existingCount + qIdx + 1}</h3>
                            <p>Enter the question text, options, and select the correct answer.</p>
                        </div>
                        <button type="button" class="btn btn-sm btn-danger-soft" data-remove-question aria-label="Remove question">
                            <i class="bi bi-trash-fill"></i>
                        </button>
                    </div>
                    <div class="question-card-body">
                        <div class="question-field-group">
                            <label class="form-label">Question Text <span class="required-mark">*</span></label>
                            <div class="math-input-wrap" data-math-input-wrap>
                                <textarea name="questions[${qIdx}][question_text]" rows="2" class="form-control" placeholder="Enter question text or math expression..." required data-math-textarea></textarea>
                                <button type="button" class="math-toolbar-toggle" data-math-toggle aria-label="Math tools" title="Math tools"><i class="bi bi-123"></i></button>
                                <div class="math-toolbar-popover" data-math-toolbar hidden>
                                    <div class="math-toolbar-header"><span>Math Tools</span><button type="button" class="btn-close btn-close-sm" data-math-close aria-label="Close"></button></div>
                                    <div class="math-toolbar-grid">
                                        <button type="button" class="math-tool-btn" data-math-insert="\\frac{}{}" title="Fraction">a/b</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="^{}" title="Superscript">x²</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="_{}" title="Subscript">x₂</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\sqrt{}" title="Square Root">√</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\pi" title="Pi">π</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\alpha" title="Alpha">α</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\beta" title="Beta">β</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\theta" title="Theta">θ</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\pm" title="±">±</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\times" title="×">×</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\div" title="÷">÷</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\leq" title="≤">≤</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\geq" title="≥">≥</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\neq" title="≠">≠</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\infty" title="∞">∞</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\sum_{}^{}" title="Σ">Σ</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\int_{}^{}" title="∫">∫</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\rightarrow" title="→">→</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\left( \\right)" title="( )">( )</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\left[ \\right]" title="[ ]">[ ]</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\left\\{ \\right\\}" title="{ }">{ }</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\cdot" title="·">·</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\Delta" title="Δ">Δ</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\lambda" title="λ">λ</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\mu" title="μ">μ</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\sigma" title="σ">σ</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\omega" title="ω">ω</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\degree" title="°">°</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\angle" title="∠">∠</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\perp" title="⊥">⊥</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\parallel" title="∥">∥</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\cong" title="≅">≅</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\sim" title="∼">∼</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\in" title="∈">∈</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\notin" title="∉">∉</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\subset" title="⊂">⊂</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\cup" title="∪">∪</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\cap" title="∩">∩</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\emptyset" title="∅">∅</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\therefore" title="∴">∴</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\because" title="∵">∵</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\forall" title="∀">∀</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\exists" title="∃">∃</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\neg" title="¬">¬</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\land" title="∧">∧</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\lor" title="∨">∨</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\implies" title="⇒">⇒</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\iff" title="⇔">⇔</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\overline{}" title="‾">‾</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\overrightarrow{}" title="→">→</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\begin{cases} & \\\\ & \\end{cases}" title="{ }">{ }</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\begin{matrix} & \\\\ & \\end{matrix}" title="[ ]">[ ]</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row g-2 question-meta-row">
                            <div class="col-md-6">
                                <label class="form-label">Marks <span class="required-mark">*</span></label>
                                <input type="number" step="1" min="1" name="questions[${qIdx}][marks]" value="${parseInt(form.dataset.defaultMarks || '1', 10)}" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Category <span class="required-mark">*</span></label>
                                <select name="questions[${qIdx}][question_category_id]" class="form-select form-control" required>${categoryOptionsHtml()}</select>
                            </div>
                        </div>
                        <div class="option-builder" data-option-builder>
                            <label class="form-label">Answer Options <span class="required-mark">*</span></label>
                            <div data-option-list>
                                ${['A', 'B', 'C', 'D'].map((letter, i) => `
                                    <div class="option-row" data-option-row>
                                        <label class="option-badge">${letter}</label>
                                        <div class="option-input-wrap">
                                            <div class="math-input-wrap" data-math-input-wrap>
                                                <textarea name="questions[${qIdx}][options][]" rows="1" class="form-control" placeholder="Option ${letter} text or math expression" required data-math-textarea></textarea>
                                                <button type="button" class="math-toolbar-toggle" data-math-toggle aria-label="Math tools" title="Math tools"><i class="bi bi-123"></i></button>
                                                <div class="math-toolbar-popover" data-math-toolbar hidden>
                                                    <div class="math-toolbar-header"><span>Math Tools</span><button type="button" class="btn-close btn-close-sm" data-math-close aria-label="Close"></button></div>
                                                    <div class="math-toolbar-grid">
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\frac{}{}" title="Fraction">a/b</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="^{}" title="Superscript">x²</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="_{}" title="Subscript">x₂</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\sqrt{}" title="Square Root">√</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\pi" title="Pi">π</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\alpha" title="Alpha">α</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\beta" title="Beta">β</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\theta" title="Theta">θ</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\pm" title="±">±</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\times" title="×">×</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\div" title="÷">÷</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\leq" title="≤">≤</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\geq" title="≥">≥</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\neq" title="≠">≠</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\infty" title="∞">∞</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\sum_{}^{}" title="Σ">Σ</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\int_{}^{}" title="∫">∫</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\rightarrow" title="→">→</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\left( \\right)" title="( )">( )</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\left[ \\right]" title="[ ]">[ ]</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\left\\{ \\right\\}" title="{ }">{ }</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\cdot" title="·">·</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\Delta" title="Δ">Δ</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\lambda" title="λ">λ</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\mu" title="μ">μ</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\sigma" title="σ">σ</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\omega" title="ω">ω</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\degree" title="°">°</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\angle" title="∠">∠</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\perp" title="⊥">⊥</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\parallel" title="∥">∥</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\cong" title="≅">≅</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\sim" title="∼">∼</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\in" title="∈">∈</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\notin" title="∉">∉</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\subset" title="⊂">⊂</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\cup" title="∪">∪</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\cap" title="∩">∩</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\emptyset" title="∅">∅</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\therefore" title="∴">∴</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\because" title="∵">∵</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\forall" title="∀">∀</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\exists" title="∃">∃</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\neg" title="¬">¬</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\land" title="∧">∧</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\lor" title="∨">∨</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\implies" title="⇒">⇒</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\iff" title="⇔">⇔</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\overline{}" title="‾">‾</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\overrightarrow{}" title="→">→</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\begin{cases} & \\\\ & \\end{cases}" title="{ }">{ }</button>
                                                        <button type="button" class="math-tool-btn" data-math-insert="\\begin{matrix} & \\\\ & \\end{matrix}" title="[ ]">[ ]</button>
                                                    </div>
                                                </div>
                                            </div>
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
                        <div class="question-explanation-field">
                            <label class="form-label">Explanation <span class="text-muted fw-normal">(optional)</span></label>
                            <div class="math-input-wrap" data-math-input-wrap>
                                <textarea name="questions[${qIdx}][explanation]" rows="1" class="form-control" placeholder="Enter explanation text or math expression..." data-math-textarea></textarea>
                                <button type="button" class="math-toolbar-toggle" data-math-toggle aria-label="Math tools" title="Math tools"><i class="bi bi-123"></i></button>
                                <div class="math-toolbar-popover" data-math-toolbar hidden>
                                    <div class="math-toolbar-header"><span>Math Tools</span><button type="button" class="btn-close btn-close-sm" data-math-close aria-label="Close"></button></div>
                                    <div class="math-toolbar-grid">
                                        <button type="button" class="math-tool-btn" data-math-insert="\\frac{}{}" title="Fraction">a/b</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="^{}" title="Superscript">x²</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="_{}" title="Subscript">x₂</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\sqrt{}" title="Square Root">√</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\pi" title="Pi">π</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\alpha" title="Alpha">α</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\beta" title="Beta">β</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\theta" title="Theta">θ</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\pm" title="±">±</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\times" title="×">×</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\div" title="÷">÷</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\leq" title="≤">≤</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\geq" title="≥">≥</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\neq" title="≠">≠</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\infty" title="∞">∞</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\sum_{}^{}" title="Σ">Σ</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\int_{}^{}" title="∫">∫</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\rightarrow" title="→">→</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\left( \\right)" title="( )">( )</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\left[ \\right]" title="[ ]">[ ]</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\left\\{ \\right\\}" title="{ }">{ }</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\cdot" title="·">·</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\Delta" title="Δ">Δ</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\lambda" title="λ">λ</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\mu" title="μ">μ</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\sigma" title="σ">σ</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\omega" title="ω">ω</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\degree" title="°">°</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\angle" title="∠">∠</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\perp" title="⊥">⊥</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\parallel" title="∥">∥</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\cong" title="≅">≅</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\sim" title="∼">∼</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\in" title="∈">∈</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\notin" title="∉">∉</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\subset" title="⊂">⊂</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\cup" title="∪">∪</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\cap" title="∩">∩</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\emptyset" title="∅">∅</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\therefore" title="∴">∴</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\because" title="∵">∵</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\forall" title="∀">∀</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\exists" title="∃">∃</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\neg" title="¬">¬</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\land" title="∧">∧</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\lor" title="∨">∨</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\implies" title="⇒">⇒</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\iff" title="⇔">⇔</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\overline{}" title="‾">‾</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\overrightarrow{}" title="→">→</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\begin{cases} & \\\\ & \\end{cases}" title="{ }">{ }</button>
                                        <button type="button" class="math-tool-btn" data-math-insert="\\begin{matrix} & \\\\ & \\end{matrix}" title="[ ]">[ ]</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                list.appendChild(block);
                wireOptions(block);
                renumberAll();
                const textarea = block.querySelector('[data-math-textarea]');
                if (textarea) {
                    textarea.scrollIntoView({behavior: 'smooth', block: 'center'});
                    textarea.focus();
                }
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
        }
        })();
    </script>
@endpush