@php
    $mathId = $mathId ?? 'summary-' . Str::random(8);
    $mathValue = $mathValue ?? '';
    $mathPlaceholder = $mathPlaceholder ?? '';
    $mathRows = $mathRows ?? 6;
    $mathName = $mathName ?? null;
    $mathClass = $mathClass ?? '';
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
    >{{ $mathValue }}</textarea>
</div>

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

                    toggleBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        var willOpen = toolbar.hidden;
                        document.querySelectorAll('[data-math-toolbar]').forEach(function (t) {
                            if (t !== toolbar) t.hidden = true;
                        });
                        toolbar.hidden = !willOpen;

                        if (willOpen) {
                            // Carry over whatever the user already selected directly in the
                            // CKEditor content (e.g. typed "x2", selected the "2") so the
                            // template buttons below have something to wrap instead of
                            // inserting an empty {} placeholder.
                            var selection = editor.model.document.selection;
                            if (! selection.isCollapsed) {
                                capturedRange = selection.getFirstRange();
                                stagingInput.value = selectedEditorText(selection);
                            } else {
                                capturedRange = null;
                                stagingInput.value = '';
                            }
                            stagingInput.focus();
                            stagingInput.select();
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
                            var snippet = btn.dataset.mathInsert;
                            var selectionStart = stagingInput.selectionStart ?? stagingInput.value.length;
                            var selectionEnd = stagingInput.selectionEnd ?? stagingInput.value.length;
                            var value = stagingInput.value;
                            var selected = value.slice(selectionStart, selectionEnd);

                            // Where the snippet gets inserted, and what (if anything) it
                            // replaces in the input's current value.
                            var replaceFrom = selectionStart;
                            var replaceTo = selectionEnd;
                            var insertText = snippet;

                            if (selected !== '') {
                                var placeholderIndex = snippet.indexOf('{}');
                                if (placeholderIndex !== -1) {
                                    // A template button (^{}, _{}, \sqrt{}, \frac{}{}, ...) has an
                                    // empty {} placeholder. The user selected text first (e.g.
                                    // typed "x2", selected "2", then clicked Superscript) — wrap
                                    // that selection inside the first placeholder ("^{2}") instead
                                    // of blindly inserting the bare template and deleting it.
                                    insertText = snippet.slice(0, placeholderIndex + 1) + selected + snippet.slice(placeholderIndex + 1);
                                } else {
                                    // A plain symbol (\pi, \times, ...) has nothing to wrap into —
                                    // keep the selection and insert the symbol right after it
                                    // instead of overwriting it.
                                    replaceFrom = replaceTo = selectionEnd;
                                }
                            }

                            stagingInput.value = value.slice(0, replaceFrom) + insertText + value.slice(replaceTo);
                            stagingInput.selectionStart = stagingInput.selectionEnd = replaceFrom + insertText.length;
                            stagingInput.focus();
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
