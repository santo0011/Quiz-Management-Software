@php
    $mathId = $mathId ?? 'summary-' . Str::random(8);
    $mathValue = $mathValue ?? '';
    $mathPlaceholder = $mathPlaceholder ?? '';
    $mathRows = $mathRows ?? 6;
    $mathName = $mathName ?? null;
    $mathClass = $mathClass ?? '';
@endphp

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
