@php
    $guardianType = $useOldInput ? old('guardian_type', 'new') : 'new';
    $oldGuardianId = $useOldInput ? old('guardian_id') : null;
    $oldGuardian = $oldGuardianId ? \App\Models\Guardian::find($oldGuardianId) : null;
@endphp

<div class="col-12">
    <label class="form-label d-block">Guardian Type <span class="required-mark">*</span></label>
    <div class="row row-cols-2 g-2">
        <div class="col">
            <label class="form-check module-check">
                <input type="radio" name="guardian_type" value="new" class="form-check-input" data-guardian-type-input @checked($guardianType !== 'existing')>
                <span><i class="bi bi-person-plus-fill me-1"></i>New Guardian</span>
            </label>
        </div>
        <div class="col">
            <label class="form-check module-check">
                <input type="radio" name="guardian_type" value="existing" class="form-check-input" data-guardian-type-input @checked($guardianType === 'existing')>
                <span><i class="bi bi-people-fill me-1"></i>Existing Guardian</span>
            </label>
        </div>
    </div>
    @if ($useOldInput)
        @error('guardian_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    @endif
</div>

<div class="col-md-6" data-guardian-new-field>
    <label for="guardian_name" class="form-label">Guardian Name <span class="required-mark">*</span></label>
    <input id="guardian_name" type="text" name="guardian_name" value="{{ $useOldInput ? old('guardian_name') : '' }}" class="form-control{{ $useOldInput && $errors->has('guardian_name') ? ' is-invalid' : '' }}" maxlength="255" data-guardian-name-input>
    @if ($useOldInput)
        @error('guardian_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    @endif
</div>

<div class="col-md-6" data-guardian-new-field>
    <label for="guardian_email" class="form-label">Guardian Email <span class="required-mark">*</span></label>
    <input id="guardian_email" type="email" name="guardian_email" value="{{ $useOldInput ? old('guardian_email') : '' }}" class="form-control{{ $useOldInput && $errors->has('guardian_email') ? ' is-invalid' : '' }}" maxlength="255" data-guardian-email-input>
    @if ($useOldInput)
        @error('guardian_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    @endif
</div>

<div class="col-12" data-guardian-existing-field data-old-name="{{ $oldGuardian?->name }}" data-old-email="{{ $oldGuardian?->email }}" hidden>
    <label class="form-label">Select Guardian <span class="required-mark">*</span></label>
    <div class="guardian-search-box" data-guardian-search-wrap>
        <i class="bi bi-search guardian-search-icon"></i>
        <input type="text" class="form-control guardian-search-input" placeholder="Search by guardian name or email..." autocomplete="off" data-guardian-search-input>
        <div class="guardian-search-dropdown" data-guardian-dropdown hidden></div>
    </div>
    <input type="hidden" name="guardian_id" value="{{ $useOldInput ? old('guardian_id') : '' }}" data-guardian-id-input>

    <div class="guardian-selected-card d-none" data-guardian-selected-card>
        <div class="guardian-selected-icon"><i class="bi bi-person-check-fill"></i></div>
        <div class="guardian-selected-info">
            <strong data-guardian-selected-name></strong>
            <span data-guardian-selected-email></span>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-guardian-clear-btn>
            <i class="bi bi-x-lg"></i> Change
        </button>
    </div>
    @if ($useOldInput)
        @error('guardian_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    @endif
    <div class="form-text">Search by name or email to link this student to an existing guardian — the same guardian can have multiple students.</div>
</div>

@push('scripts')
    <script>
        (function () {
            document.querySelectorAll('[data-guardian-picker-root]').forEach(function (root) {
                const searchUrl = root.dataset.guardianSearchUrl;
                const typeInputs = root.querySelectorAll('[data-guardian-type-input]');
                const newFields = root.querySelectorAll('[data-guardian-new-field]');
                const existingField = root.querySelector('[data-guardian-existing-field]');
                const nameInput = root.querySelector('[data-guardian-name-input]');
                const emailInput = root.querySelector('[data-guardian-email-input]');
                const idInput = root.querySelector('[data-guardian-id-input]');
                const searchInput = root.querySelector('[data-guardian-search-input]');
                const dropdown = root.querySelector('[data-guardian-dropdown]');
                const selectedCard = root.querySelector('[data-guardian-selected-card]');
                const selectedName = root.querySelector('[data-guardian-selected-name]');
                const selectedEmail = root.querySelector('[data-guardian-selected-email]');
                const clearBtn = root.querySelector('[data-guardian-clear-btn]');

                if (! typeInputs.length || ! searchUrl) {
                    return;
                }

                function setMode(mode) {
                    const isExisting = mode === 'existing';
                    newFields.forEach((el) => el.classList.toggle('d-none', isExisting));
                    if (existingField) existingField.hidden = ! isExisting;
                    if (nameInput) nameInput.required = ! isExisting;
                    if (emailInput) emailInput.required = ! isExisting;

                    if (isExisting) {
                        if (nameInput) nameInput.value = '';
                        if (emailInput) emailInput.value = '';
                    } else {
                        if (idInput) idInput.value = '';
                        selectedCard?.classList.add('d-none');
                        if (searchInput) searchInput.value = '';
                        if (dropdown) dropdown.hidden = true;
                    }
                }

                typeInputs.forEach((input) => {
                    input.addEventListener('change', () => {
                        if (input.checked) setMode(input.value);
                    });
                });

                const checkedInput = root.querySelector('[data-guardian-type-input]:checked');
                setMode(checkedInput ? checkedInput.value : 'new');

                if (idInput && idInput.value && existingField && selectedName && selectedEmail) {
                    const oldEmail = existingField.dataset.oldEmail || '';
                    if (oldEmail) {
                        selectedName.textContent = existingField.dataset.oldName || 'Guardian';
                        selectedEmail.textContent = oldEmail;
                        selectedCard?.classList.remove('d-none');
                    }
                }

                function escapeHtml(value) {
                    const div = document.createElement('div');
                    div.textContent = value ?? '';

                    return div.innerHTML;
                }

                function renderResults(guardians) {
                    if (! dropdown) return;

                    if (! guardians.length) {
                        dropdown.innerHTML = '<div class="guardian-dropdown-empty">No matching guardian found.</div>';
                        dropdown.hidden = false;

                        return;
                    }

                    dropdown.innerHTML = guardians.map((guardian) => (
                        '<button type="button" class="guardian-dropdown-item" data-id="' + guardian.id + '" data-name="' + escapeHtml(guardian.name) + '" data-email="' + escapeHtml(guardian.email) + '">' +
                            '<strong>' + escapeHtml(guardian.name) + '</strong>' +
                            '<span>' + escapeHtml(guardian.email) + '</span>' +
                        '</button>'
                    )).join('');
                    dropdown.hidden = false;
                }

                let debounceTimer;
                searchInput?.addEventListener('input', () => {
                    clearTimeout(debounceTimer);
                    const query = searchInput.value.trim();

                    if (query.length < 2) {
                        if (dropdown) {
                            dropdown.hidden = true;
                            dropdown.innerHTML = '';
                        }

                        return;
                    }

                    debounceTimer = setTimeout(() => {
                        fetch(searchUrl + '?q=' + encodeURIComponent(query), { headers: { Accept: 'application/json' } })
                            .then((response) => response.json())
                            .then(renderResults)
                            .catch(() => {
                                if (dropdown) dropdown.hidden = true;
                            });
                    }, 250);
                });

                dropdown?.addEventListener('click', (event) => {
                    const item = event.target.closest('[data-id]');
                    if (! item || ! idInput) return;

                    idInput.value = item.dataset.id;
                    if (selectedName) selectedName.textContent = item.dataset.name;
                    if (selectedEmail) selectedEmail.textContent = item.dataset.email;
                    selectedCard?.classList.remove('d-none');
                    dropdown.hidden = true;
                    if (searchInput) searchInput.value = '';
                });

                clearBtn?.addEventListener('click', () => {
                    if (idInput) idInput.value = '';
                    selectedCard?.classList.add('d-none');
                    searchInput?.focus();
                });

                document.addEventListener('click', (event) => {
                    if (! dropdown || dropdown.hidden) return;
                    if (root.contains(event.target) && (event.target.closest('[data-guardian-search-input]') || event.target.closest('[data-guardian-dropdown]'))) {
                        return;
                    }
                    dropdown.hidden = true;
                });
            });
        })();
    </script>
@endpush
