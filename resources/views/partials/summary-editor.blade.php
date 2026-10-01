@php
    $mathId = $mathId ?? 'summary-' . Str::random(8);
    $mathValue = $mathValue ?? '';
    $mathPlaceholder = $mathPlaceholder ?? '';
    $mathRows = $mathRows ?? 6;
    $mathName = $mathName ?? null;
    $mathClass = $mathClass ?? '';
    // Opt-in "Normal Text / Math Equation" switch (question text and Summary
    // content); without it the editor keeps its "+ Insert Math" popover.
    $mathMode = $mathMode ?? false;
@endphp

<div class="ckeditor-field-wrap" data-ckeditor-field-wrap>
    <div class="ckeditor-math-wrap" data-ckeditor-math-wrap>
        <button type="button" class="ckeditor-math-toggle" data-math-toggle aria-label="Insert math equation" title="Insert Math Equation">
            <i class="bi bi-plus-square-fill"></i> Insert Math
        </button>
        <div class="math-toolbar-popover" data-math-toolbar hidden>
            <div class="math-toolbar-header">
                <span>Insert Math Equation</span>
                <button type="button" class="btn-close btn-close-sm" data-math-close aria-label="Close"></button>
            </div>
            <div class="ckeditor-math-staging">
                <input type="text" class="form-control form-control-sm" data-math-staging placeholder="e.g. \frac{1}{2} + x^2" autocomplete="off">
            </div>
            <div class="math-toolbar-grid">
                @include('partials.ckeditor-math-buttons')
            </div>
            <div class="ckeditor-math-actions">
                <button type="button" class="btn btn-sm btn-primary" data-math-confirm>Insert Equation</button>
            </div>
        </div>
    </div>

    {{--
        No native `required` attribute here on purpose. CKEditor replaces this
        textarea with its own UI and hides the original (display:none), and the
        browser's native "required field" constraint check runs BEFORE any
        `submit` JS listeners fire — including CKEditor's own data sync. It finds
        the still-empty, now-unfocusable textarea, tries to focus it to show the
        validation bubble, can't, and silently cancels the whole submission
        ("An invalid form control ... is not focusable" in the console) — even
        when the user typed real content into CKEditor. Empty-content validation
        is instead enforced server-side (see PassageGroupRequest).
    --}}
    <textarea
        id="{{ $mathId }}"
        name="{{ $mathName }}"
        rows="{{ $mathRows }}"
        class="form-control {{ $mathClass }}"
        placeholder="{{ $mathPlaceholder }}"
        data-summary-editor
        @if ($mathMode) data-math-mode @endif
    >{{ $mathValue }}</textarea>
</div>

@if ($mathMode)
    @once
        {{--
            The Math Equation mode field is the exact Answer Options Math Tool
            (partials.math-editor + math-editor-init), rendered once here and
            cloned per question — including the JS-built "Add Another Question"
            cards — so both tools share the same symbols and insert behaviour.
        --}}
        <template data-math-mode-template>
            @include('partials.math-editor', [
                'mathId' => 'math_mode_template',
                'mathRows' => 2,
                'mathPlaceholder' => 'Enter math equation (e.g. x² + 2x + 1)',
            ])
        </template>
    @endonce
@endif

@once
    @push('scripts')
        <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
        <script>
            (function () {
                function Base64UploadAdapter(loader) {
                    this.loader = loader;
                }
                Base64UploadAdapter.prototype.upload = function () {
                    return this.loader.file.then(function (file) {
                        return new Promise(function (resolve, reject) {
                            var reader = new FileReader();
                            reader.onload = function () { resolve({ default: reader.result }); };
                            reader.onerror = function (error) { reject(error); };
                            reader.readAsDataURL(file);
                        });
                    });
                };
                Base64UploadAdapter.prototype.abort = function () {};

                function Base64UploadAdapterPlugin(editor) {
                    // Guard: if the loaded CKEditor build doesn't expose FileRepository for
                    // any reason, skip wiring the adapter instead of throwing — a throw here
                    // rejects the whole ClassicEditor.create() call below, which previously
                    // left the source textarea un-synced and Save Summary silently failing.
                    if (! editor.plugins.has('FileRepository')) {
                        return;
                    }

                    editor.plugins.get('FileRepository').createUploadAdapter = function (loader) {
                        return new Base64UploadAdapter(loader);
                    };
                }

                // Inserts a math-tool button's snippet into a staging input at the
                // cursor (used by the Insert Math popover's staging input).
                function insertMathSnippet(stagingInput, snippet) {
                    var selectionStart = stagingInput.selectionStart ?? stagingInput.value.length;
                    var selectionEnd = stagingInput.selectionEnd ?? stagingInput.value.length;
                    var value = stagingInput.value;
                    var selected = value.slice(selectionStart, selectionEnd);
                    var placeholderIndex = snippet.indexOf('{}');

                    // Where the snippet gets inserted, and what (if anything) it
                    // replaces in the input's current value.
                    var replaceFrom = selectionStart;
                    var replaceTo = selectionEnd;
                    var insertText = snippet;

                    // Cursor position after inserting, as an offset into insertText —
                    // defaults to the end (right after the whole snippet).
                    var cursorOffset = insertText.length;

                    if (selected !== '') {
                        if (placeholderIndex !== -1) {
                            // A template button (^{}, _{}, \sqrt{}, \frac{}{}, ...) has an
                            // empty {} placeholder. The user selected text first (e.g.
                            // typed "x2", selected "2", then clicked Superscript) — wrap
                            // that selection inside the first placeholder ("^{2}") instead
                            // of blindly inserting the bare template and deleting it.
                            insertText = snippet.slice(0, placeholderIndex + 1) + selected + snippet.slice(placeholderIndex + 1);
                            cursorOffset = insertText.length;
                        } else {
                            // A plain symbol (\pi, \times, ...) has nothing to wrap into —
                            // keep the selection and insert the symbol right after it
                            // instead of overwriting it.
                            replaceFrom = replaceTo = selectionEnd;
                            cursorOffset = insertText.length;
                        }
                    } else if (placeholderIndex !== -1) {
                        // Nothing was selected and this is a template with an empty {}
                        // placeholder (e.g. clicking Superscript with no prior selection
                        // produces bare "^{}") — land the cursor INSIDE that first pair of
                        // braces so the user can type the exponent/argument immediately,
                        // instead of after the closing brace where a repeat click of the
                        // same button would just append another empty "^{}" right next to
                        // it with no visible difference.
                        cursorOffset = placeholderIndex + 1;
                    }

                    stagingInput.value = value.slice(0, replaceFrom) + insertText + value.slice(replaceTo);
                    stagingInput.selectionStart = stagingInput.selectionEnd = replaceFrom + cursorOffset;
                    stagingInput.focus();
                }

                // Wires the "Insert Math" popover that sits above a CKEditor
                // instance to that specific editor. Symbol/template buttons build
                // up a raw LaTeX expression in a staging input (same click-to-
                // insert-at-cursor pattern as the plain-textarea Math tool); only
                // when "Insert Equation" is clicked is the staged expression
                // wrapped in a single \( \) pair and inserted into the editor —
                // wrapping each button individually would leave structural
                // snippets like "^{}" or "\frac{}{}" broken across separate math
                // regions instead of composed into one equation.
                function initMathToolFor(textarea, editor) {
                    var container = textarea.closest('[data-ckeditor-field-wrap]');
                    var wrap = container ? container.querySelector('[data-ckeditor-math-wrap]') : null;

                    if (! wrap || wrap.dataset.mathReady === '1') {
                        return;
                    }
                    wrap.dataset.mathReady = '1';

                    var toggleBtn = wrap.querySelector('[data-math-toggle]');
                    var toolbar = wrap.querySelector('[data-math-toolbar]');
                    var closeBtn = wrap.querySelector('[data-math-close]');
                    var stagingInput = wrap.querySelector('[data-math-staging]');
                    var confirmBtn = wrap.querySelector('[data-math-confirm]');

                    if (! toggleBtn || ! toolbar || ! stagingInput || ! confirmBtn) {
                        return;
                    }

                    var closeToolbar = function () { toolbar.hidden = true; };

                    // The range (if any) the editor's own selection covered when the
                    // popover was opened — captured so "Insert Equation" can replace
                    // exactly that text instead of just inserting alongside it.
                    var capturedRange = null;

                    // selection.getRanges() / range.getItems() are iterators (generators),
                    // not arrays — no .length, no index access. Walk them with for..of.
                    var selectedEditorText = function (selection) {
                        var text = '';
                        for (var range of selection.getRanges()) {
                            for (var item of range.getItems()) {
                                if (typeof item.data === 'string') {
                                    text += item.data;
                                }
                            }
                        }
                        return text;
                    };

                    // When nothing is selected, a plain click into "Insert Math" right
                    // after typing e.g. "x" would otherwise build the equation in total
                    // isolation from that "x" — inserting a disconnected "\(^{2}\)" next
                    // to it (an empty-based superscript) instead of a merged "x²". Pull
                    // in the run of letters/digits immediately before the cursor (the
                    // word being typed) the same way an explicit selection is carried
                    // over, so it becomes the base the template buttons build onto.
                    var wordBeforeCollapsedCursor = function (selection) {
                        var position = selection.getFirstPosition();
                        var block = position.parent;

                        if (! block || typeof block.getChildren !== 'function') {
                            return null;
                        }

                        var textBefore = '';
                        var range = editor.model.createRange(editor.model.createPositionAt(block, 0), position);
                        for (var item of range.getItems()) {
                            if (typeof item.data === 'string') {
                                textBefore += item.data;
                            }
                        }

                        var match = /[A-Za-z0-9]+$/.exec(textBefore);
                        if (! match) {
                            return null;
                        }

                        var startOffset = position.offset - match[0].length;

                        return {
                            text: match[0],
                            range: editor.model.createRange(editor.model.createPositionAt(block, startOffset), position),
                        };
                    };

                    toggleBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        var willOpen = toolbar.hidden;
                        document.querySelectorAll('[data-math-toolbar]').forEach(function (t) {
                            if (t !== toolbar) t.hidden = true;
                        });
                        toolbar.hidden = !willOpen;

                        if (willOpen) {
                            var selection = editor.model.document.selection;

                            if (! selection.isCollapsed) {
                                // Carry over whatever the user already selected directly in
                                // the CKEditor content (e.g. typed "x2", selected the "2")
                                // so the template buttons below have something to wrap
                                // instead of inserting an empty {} placeholder.
                                capturedRange = selection.getFirstRange();
                                stagingInput.value = selectedEditorText(selection);
                                stagingInput.focus();
                                stagingInput.select();
                            } else {
                                var preceding = wordBeforeCollapsedCursor(selection);

                                if (preceding) {
                                    capturedRange = preceding.range;
                                    stagingInput.value = preceding.text;
                                } else {
                                    capturedRange = null;
                                    stagingInput.value = '';
                                }

                                stagingInput.focus();
                                // Cursor at the END, not selected — a captured word like "x"
                                // should stay put as the base; clicking a template button
                                // (e.g. Superscript) should append "^{}" right after it, not
                                // wrap "x" inside the placeholder.
                                stagingInput.selectionStart = stagingInput.selectionEnd = stagingInput.value.length;
                            }
                        }
                    });

                    closeBtn && closeBtn.addEventListener('click', function () {
                        closeToolbar();
                        editor.editing.view.focus();
                    });

                    document.addEventListener('click', function (e) {
                        if (! wrap.contains(e.target)) {
                            closeToolbar();
                        }
                    });

                    toolbar.querySelectorAll('[data-math-insert]').forEach(function (btn) {
                        btn.addEventListener('click', function (e) {
                            e.preventDefault();
                            e.stopPropagation();
                            insertMathSnippet(stagingInput, btn.dataset.mathInsert);
                        });
                    });

                    var insertStagedEquation = function () {
                        var latex = stagingInput.value.trim();

                        if (latex === '') {
                            closeToolbar();
                            return;
                        }

                        editor.model.change(function (writer) {
                            // Replace whatever text was originally selected in the editor
                            // (captured when the popover opened) with the finished equation,
                            // instead of just inserting it alongside — otherwise the source
                            // text the user built the equation from (e.g. "x2") is left
                            // behind untouched next to the new \(...\) fragment.
                            if (capturedRange) {
                                writer.remove(capturedRange);
                                writer.insertText('\\(' + latex + '\\)', capturedRange.start);
                            } else {
                                var insertPosition = editor.model.document.selection.getFirstPosition();
                                writer.insertText('\\(' + latex + '\\)', insertPosition);
                            }
                        });
                        editor.editing.view.focus();
                        stagingInput.value = '';
                        capturedRange = null;
                        closeToolbar();
                    };

                    confirmBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        insertStagedEquation();
                    });

                    stagingInput.addEventListener('keydown', function (e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            insertStagedEquation();
                        }
                    });
                }

                // "Normal Text / Math Equation" switch for question text (opt-in via
                // data-math-mode). Normal Text is the regular CKEditor. Math Equation
                // temporarily swaps the editor for the exact Answer Options Math Tool
                // field (cloned from the <template data-math-mode-template> rendered
                // by partials.math-editor, and wired by math-editor-init like every
                // option field). The editor is only hidden, never destroyed, so
                // switching back and forth keeps the question content intact.
                var mathModeTemplateHtml = null;
                function mathModeFieldHtml() {
                    // Cached on first use: the <template> lives inside the first
                    // question card, which the user may later remove.
                    if (mathModeTemplateHtml === null) {
                        var template = document.querySelector('template[data-math-mode-template]');
                        mathModeTemplateHtml = template ? template.innerHTML : '';
                    }
                    return mathModeTemplateHtml;
                }

                function initMathModeFor(textarea, editor, form) {
                    var container = textarea.closest('[data-ckeditor-field-wrap]');
                    var mathWrap = container ? container.querySelector('[data-ckeditor-math-wrap]') : null;
                    var fieldHtml = mathModeFieldHtml();

                    if (! mathWrap || fieldHtml === '' || container.querySelector('[data-math-mode-switch]')) {
                        return;
                    }

                    // Math Equation mode replaces the "+ Insert Math" popover for
                    // question text.
                    mathWrap.querySelectorAll('[data-math-toggle], [data-math-toolbar]').forEach(function (el) {
                        el.remove();
                    });

                    var switcher = document.createElement('div');
                    switcher.className = 'question-mode-switch';
                    switcher.setAttribute('data-math-mode-switch', '');
                    switcher.setAttribute('role', 'group');
                    switcher.setAttribute('aria-label', 'Question input mode');
                    switcher.innerHTML =
                        '<button type="button" class="question-mode-btn is-active" data-mode="text" aria-pressed="true"><i class="bi bi-fonts"></i> Normal Text</button>' +
                        '<button type="button" class="question-mode-btn" data-mode="math" aria-pressed="false"><i class="bi bi-calculator"></i> Math Equation</button>';
                    mathWrap.classList.add('has-mode-switch');
                    mathWrap.insertBefore(switcher, mathWrap.firstChild);

                    var panel = document.createElement('div');
                    panel.className = 'math-mode-panel';
                    panel.hidden = true;
                    panel.innerHTML = fieldHtml;

                    var input = panel.querySelector('[data-math-textarea]');
                    // The clone must not submit or duplicate the template's id.
                    input.removeAttribute('id');
                    input.removeAttribute('name');
                    panel.querySelector('[data-math-input-wrap]').removeAttribute('data-math-id');

                    // Appending it lets math-editor-init's MutationObserver wire the
                    // 123 toggle and symbol buttons, exactly as for an Answer Option.
                    container.appendChild(panel);

                    // Until the user has actually placed a cursor in the question, the
                    // editor's selection sits at the very start — append there instead
                    // so an equation added to existing text lands after it.
                    var editorTouched = false;
                    editor.editing.view.document.on('change:isFocused', function (evt, name, isFocused) {
                        if (isFocused) {
                            editorTouched = true;
                        }
                    });

                    // Moves whatever is typed in the math field into the question. Runs
                    // on Enter, when switching back to Normal Text, and on Save, so a
                    // typed equation always ends up in the question. Plain symbol text
                    // (x², ½, √, π — what the Math Tool buttons insert) goes in as-is,
                    // exactly like an Answer Option; anything typed as LaTeX (\frac,
                    // x^2, a_1) is wrapped in \( \) so MathJax typesets it on the
                    // Student Exam and result screens.
                    var commitEquation = function () {
                        // Keep it on one line — fold any newlines into spaces.
                        var value = input.value.replace(/\s*\n\s*/g, ' ').trim();

                        if (value === '') {
                            return;
                        }

                        var text = /[\\^_]/.test(value) ? '\\(' + value + '\\)' : value;

                        editor.model.change(function (writer) {
                            if (! editorTouched) {
                                var root = editor.model.document.getRoot();
                                var last = root.getChild(root.childCount - 1);
                                var target = last;

                                if (! last || ! last.is('element', 'paragraph')) {
                                    target = writer.createElement('paragraph');
                                    writer.insert(target, root, 'end');
                                }
                                writer.setSelection(target, 'end');
                            }

                            // insertContent() respects the schema (e.g. an image that is
                            // currently selected is kept and the text goes beside it) and
                            // leaves the cursor right after the inserted equation.
                            editor.model.insertContent(writer.createText(text));
                        });

                        editorTouched = true;
                        input.value = '';
                    };

                    var setMode = function (mode) {
                        var isMath = mode === 'math';

                        if (! isMath && ! panel.hidden) {
                            commitEquation();
                        }

                        switcher.querySelectorAll('[data-mode]').forEach(function (btn) {
                            var active = btn.dataset.mode === mode;
                            btn.classList.toggle('is-active', active);
                            btn.setAttribute('aria-pressed', active ? 'true' : 'false');
                        });

                        editor.ui.element.style.display = isMath ? 'none' : '';
                        panel.hidden = ! isMath;
                        panel.querySelector('[data-math-toolbar]').hidden = true;

                        if (isMath) {
                            input.focus();
                        } else {
                            editor.editing.view.focus();
                            editor.editing.view.scrollToTheSelection();
                        }
                    };

                    switcher.addEventListener('click', function (e) {
                        var btn = e.target.closest('[data-mode]');
                        if (btn) {
                            e.preventDefault();
                            setMode(btn.dataset.mode);
                        }
                    });

                    input.addEventListener('keydown', function (e) {
                        if (e.key === 'Enter' && ! e.shiftKey) {
                            e.preventDefault();
                            setMode('text');
                        }
                    });

                    // Registered before the updateSourceElement() submit listener in
                    // initSummaryEditors, so an equation still sitting in the math
                    // field is added to the question before the textarea is synced.
                    if (form) {
                        form.addEventListener('submit', function () {
                            if (panel.isConnected) {
                                commitEquation();
                            }
                        });
                    }
                }

                function initSummaryEditors(scope) {
                    var root = (scope && typeof scope.querySelectorAll === 'function') ? scope : document;

                    root.querySelectorAll('textarea[data-summary-editor]:not([data-summary-editor-ready])').forEach(function (textarea) {
                        // The Summary field usually lives inside a Bootstrap collapse that's
                        // hidden (display:none) until opened. Building CKEditor while its
                        // container has zero size can leave the editable area broken/non-
                        // interactive even after the panel becomes visible, so wait for it to
                        // actually be shown (offsetParent is null while display:none applies,
                        // to any ancestor). The shown.bs.collapse listener below re-runs this
                        // function once the panel opens, retrying any textarea skipped here.
                        if (textarea.offsetParent === null) {
                            return;
                        }

                        textarea.setAttribute('data-summary-editor-ready', '1');
                        var form = textarea.closest('form');

                        ClassicEditor.create(textarea, { extraPlugins: [Base64UploadAdapterPlugin] })
                            .then(function (editor) {
                                textarea.ckeditorInstance = editor;
                                initMathToolFor(textarea, editor);

                                if (textarea.hasAttribute('data-math-mode')) {
                                    initMathModeFor(textarea, editor, form);
                                }

                                if (! form) {
                                    return;
                                }

                                // updateSourceElement() is CKEditor's own API for writing the
                                // current editor data back into the original <textarea> — the
                                // exact mechanism the native form submit reads from. Using it
                                // (instead of manually setting .value) is what CKEditor expects
                                // integrators to call before a plain HTML form submission.
                                form.addEventListener('submit', function () {
                                    editor.updateSourceElement();
                                });
                            })
                            .catch(function (error) {
                                // CKEditor failed to initialize: fall back to the plain textarea
                                // (it was never replaced/hidden, so it still submits normally).
                                console.error('CKEditor failed to initialize, falling back to plain textarea', error);
                                textarea.removeAttribute('data-summary-editor-ready');
                            });
                    });
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', function () { initSummaryEditors(); });
                } else {
                    initSummaryEditors();
                }

                new MutationObserver(function () { initSummaryEditors(); }).observe(document.body, { childList: true, subtree: true });

                // Retry any textarea that was skipped above because its collapse panel
                // was still hidden — scoped to the panel that just opened.
                document.addEventListener('shown.bs.collapse', function (event) {
                    initSummaryEditors(event.target);
                });
            })();
        </script>
    @endpush
@endonce
