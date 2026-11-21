{{--
    Hybrid answer-option editor. Each option is a compact preview backed by a
    hidden <textarea data-option-value> (the submitted field). Only the option
    being edited gets an expanded CKEditor; opening another option, Done, Esc,
    clicking elsewhere or submitting the form collapses it back to a preview.
    Reuses the Question editor's CKEditor build, image upload adapter and
    Insert Math tool (window.QuizEditorKit, from partials.summary-editor).
--}}
<template data-option-editor-template>
    <div class="hybrid-option-editor" data-option-editor-panel data-ckeditor-field-wrap>
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
        <div class="hybrid-option-editor-host" data-option-editor-host></div>
        <div class="hybrid-option-editor-footer">
            <span class="hybrid-option-hint"><i class="bi bi-clipboard-check"></i> Paste images directly · Esc to close</span>
            <button type="button" class="btn btn-sm btn-primary" data-option-done><i class="bi bi-check2"></i> Done</button>
        </div>
    </div>
</template>
{{-- Plain-text Math Tools for the option field: same symbols as partials.math-editor, inserted as characters. --}}
<template data-option-math-tools-template>
    <div class="math-toolbar-popover hybrid-option-math-tools" data-option-math-tools data-math-toolbar hidden>
        <div class="math-toolbar-header">
            <span>Math Tools</span>
            <button type="button" class="btn-close btn-close-sm" data-option-math-close aria-label="Close"></button>
        </div>
        <div class="math-toolbar-grid">
            @include('partials.math-symbol-buttons')
        </div>
    </div>
</template>
<input type="file" accept="image/png,image/jpeg,image/gif,image/webp" multiple hidden data-option-image-input>

<script>
    (function () {
        if (window.HybridOptionEditor) {
            return;
        }

        // Must match App\Support\OptionContent::HTML_PATTERN.
        var HTML_PATTERN = /^\s*<(p|figure|ul|ol|table|blockquote|h[2-4]|pre)[\s>]/i;
        var MAX_IMAGE_BYTES = 2 * 1024 * 1024;
        var ALLOWED_IMAGE_TYPES = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];
        var TOOLBAR = ['bold', 'italic', '|', 'bulletedList', 'numberedList', '|', 'uploadImage', 'insertTable', '|', 'undo', 'redo'];

        var states = new WeakMap();
        var active = null;
        var imageTarget = null;
        var imageInput = document.querySelector('[data-option-image-input]');

        function escapeHtml(value) {
            return String(value).replace(/[&<>"']/g, function (ch) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
            });
        }

        function isHtml(value) {
            return HTML_PATTERN.test(value || '');
        }

        function isBlankHtml(html) {
            if (/<img/i.test(html)) {
                return false;
            }
            var probe = document.createElement('div');
            probe.innerHTML = html;
            return probe.textContent.replace(/ /g, ' ').trim() === '';
        }

        function isBlank(value) {
            return isHtml(value) ? isBlankHtml(value) : (value || '').trim() === '';
        }

        // Legacy plain-text options are loaded into CKEditor as an escaped
        // paragraph; they are only rewritten as HTML if the user changes them.
        function toEditorHtml(value) {
            if (isHtml(value)) {
                return value;
            }
            return (value || '').trim() === '' ? '' : '<p>' + escapeHtml(value).replace(/\r?\n/g, '<br>') + '</p>';
        }

        function typeset(element) {
            if (window.MathJax && typeof window.MathJax.typesetPromise === 'function') {
                if (typeof window.MathJax.typesetClear === 'function') {
                    window.MathJax.typesetClear([element]);
                }
                window.MathJax.typesetPromise([element]).catch(function () {});
            }
        }

        function valueField(root) {
            return root.querySelector('[data-option-value]');
        }

        var PLAIN_EDITABLE = (function () {
            var probe = document.createElement('div');
            probe.contentEditable = 'plaintext-only';
            return probe.contentEditable === 'plaintext-only' ? 'plaintext-only' : 'true';
        })();

        // A plain-text option is typed directly into its field. Rich content
        // (images, formatting, equations) can't be edited as plain text, so it
        // stays a read-only preview edited through the Edit button.
        function applyPreviewMode(root) {
            var preview = root.querySelector('[data-option-preview]');
            var plain = ! isHtml(valueField(root).value);

            if (plain) {
                preview.setAttribute('contenteditable', PLAIN_EDITABLE);
                preview.removeAttribute('aria-readonly');
            } else {
                preview.removeAttribute('contenteditable');
                preview.setAttribute('aria-readonly', 'true');
            }
            preview.setAttribute('role', 'textbox');
            // Typed text such as "$5 or $6" must stay literal, not become MathJax.
            preview.classList.toggle('tex2jax_ignore', plain);
        }

        function renderPreview(root) {
            var preview = root.querySelector('[data-option-preview]');
            var value = valueField(root).value;
            var empty = isBlank(value);

            if (isHtml(value)) {
                preview.innerHTML = empty ? '' : value;
                typeset(preview);
            } else {
                preview.textContent = value;
            }
            preview.classList.toggle('is-empty', empty);
            applyPreviewMode(root);
        }

        function insertPlainText(preview, text) {
            var selection = window.getSelection();

            if (! selection.rangeCount || ! preview.contains(selection.anchorNode)) {
                selection.selectAllChildren(preview);
                selection.collapseToEnd();
            }

            var range = selection.getRangeAt(0);
            var node = document.createTextNode(text);
            range.deleteContents();
            range.insertNode(node);
            range.setStartAfter(node);
            range.collapse(true);
            selection.removeAllRanges();
            selection.addRange(range);
            syncPlainInput(preview);
        }

        // Caret position inside each plain option field, so a Math Tools symbol
        // lands where the user was typing even after focus moved to the tools.
        var savedRanges = new WeakMap();

        document.addEventListener('selectionchange', function () {
            var selection = window.getSelection();
            if (! selection.rangeCount) {
                return;
            }
            var node = selection.anchorNode;
            var element = node && (node.nodeType === 1 ? node : node.parentElement);
            var preview = element && element.closest('[data-option-preview][contenteditable]');
            if (preview) {
                savedRanges.set(preview, selection.getRangeAt(0).cloneRange());
            }
        });

        function closeMathTools(except) {
            document.querySelectorAll('[data-option-math-tools]').forEach(function (tools) {
                if (tools !== except) {
                    tools.hidden = true;
                }
            });
        }

        function toggleMathTools(root) {
            var tools = root.querySelector('[data-option-math-tools]');
            if (! tools) {
                var template = document.querySelector('template[data-option-math-tools-template]');
                var wrapper = document.createElement('div');
                wrapper.innerHTML = template ? template.innerHTML : '';
                tools = wrapper.firstElementChild;
                root.appendChild(tools);
            }

            var willOpen = tools.hidden;
            document.querySelectorAll('[data-math-toolbar]').forEach(function (toolbar) { toolbar.hidden = true; });
            tools.hidden = ! willOpen;
        }

        function insertMathSymbol(root, symbol) {
            var preview = root.querySelector('[data-option-preview]');
            var range = savedRanges.get(preview);
            var selection = window.getSelection();

            preview.focus({ preventScroll: true });
            if (range && preview.contains(range.startContainer)) {
                selection.removeAllRanges();
                selection.addRange(range);
            }
            insertPlainText(preview, symbol);
        }

        function syncPlainInput(preview) {
            var root = preview.closest('[data-option-editor]');
            var text = preview.innerText.replace(/\r?\n/g, ' ');

            if (text.trim() === '') {
                text = '';
                preview.innerHTML = '';
            }
            valueField(root).value = text;
            preview.classList.toggle('is-empty', text === '');
            root.classList.remove('is-invalid');
        }

        function OptionImageUploadAdapter(loader) {
            this.loader = loader;
        }
        OptionImageUploadAdapter.prototype.upload = function () {
            return this.loader.file.then(function (file) {
                if (ALLOWED_IMAGE_TYPES.indexOf(file.type) === -1) {
                    window.alert('Only PNG, JPG, GIF or WEBP images can be added to an option.');
                    return Promise.reject('unsupported image type');
                }
                if (file.size > MAX_IMAGE_BYTES) {
                    window.alert('This image is larger than 2 MB. Please use a smaller or cropped image for the option.');
                    return Promise.reject('image too large');
                }
                return new Promise(function (resolve, reject) {
                    var reader = new FileReader();
                    reader.onload = function () { resolve({ default: reader.result }); };
                    reader.onerror = reject;
                    reader.readAsDataURL(file);
                });
            });
        };
        OptionImageUploadAdapter.prototype.abort = function () {};

        function OptionImageUploadPlugin(editor) {
            if (! editor.plugins.has('FileRepository')) {
                return;
            }
            editor.plugins.get('FileRepository').createUploadAdapter = function (loader) {
                return new OptionImageUploadAdapter(loader);
            };
        }

        function moveSelectionToEnd(editor) {
            editor.model.change(function (writer) {
                var root = editor.model.document.getRoot();
                var last = root.getChild(root.childCount - 1);
                writer.setSelection(last && last.is('element', 'paragraph') ? last : root, 'end');
            });
        }

        function ensureEditor(root, state) {
            if (state.ready) {
                return state.ready;
            }

            var host = state.panel.querySelector('[data-option-editor-host]');
            var letter = root.dataset.optionLetter || '';

            state.ready = window.ClassicEditor.create(host, {
                extraPlugins: [OptionImageUploadPlugin],
                toolbar: { items: TOOLBAR, shouldNotGroupWhenFull: false },
                placeholder: 'Write option ' + letter + ' — text, image or math equation',
                initialData: toEditorHtml(valueField(root).value),
            }).then(function (editor) {
                state.editor = editor;
                state.baseline = editor.getData();
                state.sourceValue = valueField(root).value;

                if (window.QuizEditorKit && typeof window.QuizEditorKit.initMathToolFor === 'function') {
                    window.QuizEditorKit.initMathToolFor(host, editor);
                }

                editor.editing.view.document.on('keydown', function (evt, data) {
                    if (data.domEvent && data.domEvent.key === 'Escape') {
                        close(root, true);
                    }
                });

                return editor;
            }).catch(function (error) {
                // CKEditor unavailable: fall back to a plain textarea so the
                // option can still be typed (stored as plain text, as before).
                console.error('Option editor failed to initialize, using plain text input', error);
                state.ready = null;
                fallbackToPlain(root, state);
                return null;
            });

            return state.ready;
        }

        function fallbackToPlain(root, state) {
            var field = valueField(root);
            state.panel.hidden = true;
            root.classList.remove('is-editing');
            root.querySelector('[data-option-compact]').hidden = true;
            field.classList.remove('d-none');
            field.classList.add('form-control');
            field.rows = 2;
            field.addEventListener('input', function () { root.classList.remove('is-invalid'); });
            root.dataset.optionPlain = '1';
            if (active && active.root === root) {
                active = null;
            }
        }

        function open(root) {
            if (root.dataset.optionPlain === '1') {
                valueField(root).focus();
                return Promise.resolve(null);
            }

            if (active && active.root !== root) {
                close(active.root, false);
            }

            var state = states.get(root);
            if (! state) {
                state = {};
                states.set(root, state);
            }

            if (! state.panel) {
                var template = document.querySelector('template[data-option-editor-template]');
                var wrapper = document.createElement('div');
                wrapper.innerHTML = template ? template.innerHTML : '';
                state.panel = wrapper.firstElementChild;
                root.appendChild(state.panel);
            }

            root.classList.add('is-editing');
            root.classList.remove('is-invalid');
            root.querySelector('[data-option-compact]').hidden = true;
            state.panel.hidden = false;
            active = { root: root };

            if (! window.ClassicEditor) {
                fallbackToPlain(root, state);
                return Promise.resolve(null);
            }

            return ensureEditor(root, state).then(function (editor) {
                // The option may have been typed in directly since the editor
                // was last open; load the current text instead of stale content.
                var current = valueField(root).value;
                if (editor && current !== state.sourceValue) {
                    editor.setData(toEditorHtml(current));
                    state.baseline = editor.getData();
                    state.sourceValue = current;
                }

                if (editor && active && active.root === root) {
                    moveSelectionToEnd(editor);
                    editor.editing.view.focus();
                }
                return editor;
            });
        }

        function commit(root) {
            var state = states.get(root);
            if (! state || ! state.editor) {
                return;
            }

            var data = state.editor.getData();
            if (data === state.baseline) {
                return;
            }

            state.baseline = data;
            valueField(root).value = isBlankHtml(data) ? '' : data;
            state.sourceValue = valueField(root).value;
            renderPreview(root);
        }

        function close(root, focusPreview) {
            var state = states.get(root);

            commit(root);

            if (state && state.panel) {
                state.panel.hidden = true;
                state.panel.querySelectorAll('[data-math-toolbar]').forEach(function (toolbar) { toolbar.hidden = true; });
            }
            root.classList.remove('is-editing');
            root.querySelector('[data-option-compact]').hidden = false;

            if (active && active.root === root) {
                active = null;
            }
            if (focusPreview) {
                root.querySelector('[data-option-preview]').focus();
            }
        }

        function destroy(root) {
            var state = states.get(root);
            if (active && active.root === root) {
                active = null;
            }
            if (state && state.editor) {
                state.editor.destroy().catch(function () {});
            }
            states.delete(root);
        }

        function insertFiles(root, files) {
            open(root).then(function (editor) {
                if (! editor || ! files.length) {
                    return;
                }
                moveSelectionToEnd(editor);
                editor.execute('uploadImage', { file: files });
            });
        }

        function insertPasted(root, html, text) {
            open(root).then(function (editor) {
                if (! editor) {
                    return;
                }
                moveSelectionToEnd(editor);
                var source = html || (text ? '<p>' + escapeHtml(text).replace(/\r?\n/g, '<br>') + '</p>' : '');
                if (source) {
                    editor.model.insertContent(editor.data.toModel(editor.data.processor.toView(source)));
                }
            });
        }

        function setLetter(root, letter) {
            root.dataset.optionLetter = letter;
            var preview = root.querySelector('[data-option-preview]');
            preview.setAttribute('aria-label', 'Option ' + letter);
            preview.dataset.placeholder = 'Click to write option ' + letter + ', or paste text / an image';
        }

        function fieldHtml(options) {
            var letter = options.letter;
            return '<div class="hybrid-option" data-option-editor data-option-letter="' + letter + '">' +
                '<textarea name="' + escapeHtml(options.name) + '" class="d-none" data-option-value></textarea>' +
                (options.idName ? '<input type="hidden" name="' + escapeHtml(options.idName) + '" value="" data-option-id>' : '') +
                '<div class="hybrid-option-compact" data-option-compact>' +
                    '<div class="hybrid-option-preview math-content option-html tex2jax_ignore is-empty" data-option-preview tabindex="0" role="textbox" contenteditable="' + PLAIN_EDITABLE + '" aria-label="Option ' + letter + '" data-placeholder="Click to write option ' + letter + ', or paste text / an image"></div>' +
                    '<div class="hybrid-option-tools">' +
                        '<button type="button" class="hybrid-option-tool" data-option-action="edit" title="Edit with rich editor" aria-label="Edit option"><i class="bi bi-pencil-square"></i></button>' +
                        '<button type="button" class="hybrid-option-tool" data-option-action="image" title="Insert image" aria-label="Insert image"><i class="bi bi-image"></i></button>' +
                        '<button type="button" class="hybrid-option-tool" data-option-action="math" title="Insert math equation" aria-label="Insert math equation"><i class="bi bi-calculator"></i></button>' +
                    '</div>' +
                '</div>' +
            '</div>';
        }

        document.addEventListener('click', function (event) {
            if (! event.target.closest('[data-option-math-tools], [data-option-action="math"]')) {
                closeMathTools();
            }

            // Clicking the field never opens CKEditor; only stop links in rich
            // content from navigating away.
            if (event.target.closest('[data-option-preview] a')) {
                event.preventDefault();
                return;
            }

            var action = event.target.closest('[data-option-action]');
            if (action) {
                event.preventDefault();
                var root = action.closest('[data-option-editor]');

                if (action.dataset.optionAction === 'image') {
                    imageTarget = root;
                    imageInput.value = '';
                    imageInput.click();
                } else if (action.dataset.optionAction === 'math' && root.querySelector('[data-option-preview][contenteditable]')) {
                    toggleMathTools(root);
                } else if (action.dataset.optionAction === 'math') {
                    // Rich content (image/formatting) can only take an equation
                    // through the rich editor's Insert Math tool.
                    open(root).then(function (editor) {
                        var toggle = editor && root.querySelector('[data-option-editor-panel] [data-math-toggle]');
                        if (toggle) {
                            toggle.click();
                        }
                    });
                } else {
                    open(root);
                }
                return;
            }

            var symbol = event.target.closest('[data-option-math-tools] [data-math-insert]');
            if (symbol) {
                event.preventDefault();
                insertMathSymbol(symbol.closest('[data-option-editor]'), symbol.dataset.mathInsert);
                return;
            }

            var mathClose = event.target.closest('[data-option-math-close]');
            if (mathClose) {
                event.preventDefault();
                var mathRoot = mathClose.closest('[data-option-editor]');
                closeMathTools();
                mathRoot.querySelector('[data-option-preview]').focus();
                return;
            }

            var done = event.target.closest('[data-option-done]');
            if (done) {
                event.preventDefault();
                close(done.closest('[data-option-editor]'), true);
            }
        });

        document.addEventListener('keydown', function (event) {
            // Options are single-line, like the plain input they replace.
            var preview = event.target.closest && event.target.closest('[data-option-preview][contenteditable]');
            if (preview && event.key === 'Enter') {
                event.preventDefault();
            }
            if (event.key === 'Escape') {
                closeMathTools();
            }
        });

        document.addEventListener('input', function (event) {
            var preview = event.target.closest && event.target.closest('[data-option-preview][contenteditable]');
            if (preview) {
                syncPlainInput(preview);
            }
        });

        // Paste into the option field: text is inserted as plain text; images
        // need the rich editor, and go through the same upload adapter as the
        // toolbar's Insert Image button.
        document.addEventListener('paste', function (event) {
            var preview = event.target.closest && event.target.closest('[data-option-preview]');
            if (! preview || ! event.clipboardData) {
                return;
            }
            event.preventDefault();

            var root = preview.closest('[data-option-editor]');
            var files = Array.prototype.filter.call(event.clipboardData.files || [], function (file) {
                return file.type.indexOf('image/') === 0;
            });

            if (files.length) {
                insertFiles(root, files);
            } else if (preview.hasAttribute('contenteditable')) {
                insertPlainText(preview, event.clipboardData.getData('text/plain').replace(/\r?\n/g, ' '));
            } else {
                insertPasted(root, event.clipboardData.getData('text/html'), event.clipboardData.getData('text/plain'));
            }
        });

        imageInput.addEventListener('change', function () {
            var files = Array.prototype.slice.call(imageInput.files || []);
            if (imageTarget && files.length) {
                insertFiles(imageTarget, files);
            }
            imageTarget = null;
        });

        // Clicking anywhere outside the option being edited collapses it.
        // CKEditor renders balloons/dropdowns in a body-level .ck-body-wrapper.
        document.addEventListener('mousedown', function (event) {
            // Keep focus (and the caret) in the option field while using Math Tools.
            if (event.target.closest('[data-option-math-tools] button, [data-option-action="math"]')) {
                event.preventDefault();
            }


            if (! active || active.root.contains(event.target) || event.target.closest('.ck-body-wrapper, .ck')) {
                return;
            }
            close(active.root, false);
        });

        // Capture phase: runs before any submit handler, so the open editor's
        // content is in its hidden field before the form is serialized.
        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (active && form.contains(active.root)) {
                close(active.root, false);
            }

            var firstEmpty = null;
            form.querySelectorAll('[data-option-editor]').forEach(function (root) {
                var empty = isBlank(valueField(root).value);
                root.classList.toggle('is-invalid', empty);
                if (empty && ! firstEmpty) {
                    firstEmpty = root;
                }
            });

            if (firstEmpty) {
                event.preventDefault();
                event.stopImmediatePropagation();
                firstEmpty.scrollIntoView({ behavior: 'smooth', block: 'center' });
                var focusTarget = firstEmpty.dataset.optionPlain === '1' ? valueField(firstEmpty) : firstEmpty.querySelector('[data-option-preview]');
                focusTarget.focus({ preventScroll: true });
            }
        }, true);

        // The Edit Question page doesn't otherwise load MathJax; previews of
        // \( \) equations need it. Deferred so a loader pushed later on the
        // same page (e.g. questions.partials.multi-form) is seen first.
        document.addEventListener('DOMContentLoaded', function () {
            if (document.querySelector('script[src*="mathjax"]')) {
                return;
            }
            if (! window.MathJax) {
                window.MathJax = { tex: { inlineMath: [['$', '$'], ['\\(', '\\)']] } };
            }
            var script = document.createElement('script');
            script.async = true;
            script.src = 'https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js';
            document.head.appendChild(script);
        });

        document.querySelectorAll('[data-option-editor]').forEach(applyPreviewMode);

        window.HybridOptionEditor = {
            fieldHtml: fieldHtml,
            setLetter: setLetter,
            destroy: destroy,
            close: close,
        };
    })();
</script>
