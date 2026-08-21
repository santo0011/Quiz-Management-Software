@php
    $mathId = $mathId ?? 'summary-' . Str::random(8);
    $mathValue = $mathValue ?? '';
    $mathPlaceholder = $mathPlaceholder ?? '';
    $mathRows = $mathRows ?? 6;
    $mathName = $mathName ?? null;
    $mathRequired = $mathRequired ?? false;
    $mathClass = $mathClass ?? '';
@endphp

<textarea
    id="{{ $mathId }}"
    name="{{ $mathName }}"
    rows="{{ $mathRows }}"
    class="form-control {{ $mathClass }}"
    placeholder="{{ $mathPlaceholder }}"
    {{ $mathRequired ? 'required' : '' }}
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
                    editor.plugins.get('FileRepository').createUploadAdapter = function (loader) {
                        return new Base64UploadAdapter(loader);
                    };
                }

                function initSummaryEditors() {
                    document.querySelectorAll('textarea[data-summary-editor]:not([data-summary-editor-ready])').forEach(function (textarea) {
                        textarea.setAttribute('data-summary-editor-ready', '1');
                        ClassicEditor.create(textarea, { extraPlugins: [Base64UploadAdapterPlugin] })
                            .then(function (editor) {
                                var form = textarea.closest('form');
                                if (form) {
                                    form.addEventListener('submit', function () {
                                        textarea.value = editor.getData();
                                    });
                                }
                            })
                            .catch(function (error) {
                                console.error('CKEditor failed to initialize', error);
                            });
                    });
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initSummaryEditors);
                } else {
                    initSummaryEditors();
                }

                new MutationObserver(initSummaryEditors).observe(document.body, { childList: true, subtree: true });
            })();
        </script>
    @endpush
@endonce
