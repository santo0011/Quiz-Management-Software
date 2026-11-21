<form method="POST" action="{{ $action }}" class="admin-form">
    @csrf
    @isset($drawerId)
        <input type="hidden" name="_drawer" value="{{ $drawerId }}">
    @endisset
    @if ($method !== 'POST')
        @method($method)
    @endif

    @php($useOldInput = isset($drawerId) ? old('_drawer') === $drawerId : true)

    <div class="mb-4">
        <label for="name" class="form-label">Branch Name <span class="required-mark">*</span></label>
        <input id="name" type="text" name="name" value="{{ $useOldInput ? old('name', $branch->name) : $branch->name }}" class="form-control{{ $useOldInput && $errors->has('name') ? ' is-invalid' : '' }}" required maxlength="255">
        @if ($useOldInput)
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        @endif
    </div>

    @php($usernameId = 'username_'.($drawerId ?? ($branch->exists ? 'edit_'.$branch->id : 'new')))
    @php($currentUsername = $branch->exists ? $branch->user?->username : null)
    <div class="mb-4">
        <label for="{{ $usernameId }}" class="form-label">Login User ID <span class="required-mark">*</span></label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
            <input id="{{ $usernameId }}" type="text" name="username"
                value="{{ $useOldInput ? old('username', $currentUsername) : $currentUsername }}"
                class="form-control{{ $useOldInput && $errors->has('username') ? ' is-invalid' : '' }}"
                required minlength="3" maxlength="50" autocomplete="off" autocapitalize="none" spellcheck="false"
                placeholder="e.g. kellyville_branch"
                data-username-check="{{ route('admin.branches.check-username') }}"
                data-username-ignore="{{ $branch->exists ? $branch->id : '' }}">
        </div>
        <div class="form-text username-check-status" data-username-status aria-live="polite">
            The branch logs in with this ID. It must be unique. Letters, numbers, dot, dash and underscore only.
        </div>
        @if ($useOldInput)
            @error('username')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        @endif
    </div>

    <div class="mb-4">
        <label for="email" class="form-label">Branch Email <span class="required-mark">*</span></label>
        <input id="email" type="email" name="email" value="{{ $useOldInput ? old('email', $branch->email) : $branch->email }}" class="form-control{{ $useOldInput && $errors->has('email') ? ' is-invalid' : '' }}" required maxlength="255">
        @if ($useOldInput)
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        @endif
        <div class="form-text">
            Login codes and the password are emailed here. The same email can be used for more than one branch.
        </div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle-fill"></i>
            {{ $button }}
        </button>
        @if ($drawer ?? false)
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">Cancel</button>
        @else
            <a href="{{ route('admin.branches.index') }}" class="btn btn-outline-secondary">Cancel</a>
        @endif
    </div>
</form>

@once
    <script>
        (function () {
            const timers = new WeakMap();

            function setStatus(input, state, text) {
                const status = input.closest('.mb-4').querySelector('[data-username-status]');
                input.classList.toggle('is-valid', state === 'ok');
                input.classList.toggle('is-invalid', state === 'bad');
                status.classList.toggle('text-success', state === 'ok');
                status.classList.toggle('text-danger', state === 'bad');
                status.textContent = text;
                input.dataset.usernameState = state;
            }

            function check(input) {
                const value = input.value.trim().toLowerCase();

                if (value === '') {
                    setStatus(input, '', 'The branch logs in with this ID. It must be unique.');
                    return;
                }

                setStatus(input, 'checking', 'Checking availability…');
                const url = new URL(input.dataset.usernameCheck, window.location.href);
                url.searchParams.set('username', value);
                if (input.dataset.usernameIgnore) {
                    url.searchParams.set('ignore', input.dataset.usernameIgnore);
                }

                fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then((response) => response.json())
                    .then((data) => {
                        if (input.value.trim().toLowerCase() !== data.username) {
                            return; // the user kept typing; a newer check is coming
                        }
                        setStatus(input, data.available ? 'ok' : 'bad', (data.available ? '✓ ' : '✗ ') + data.message);
                    })
                    .catch(() => setStatus(input, '', 'Could not check the User ID right now. It will be checked on save.'));
            }

            document.addEventListener('input', (event) => {
                const input = event.target.closest('[data-username-check]');
                if (!input) return;

                // Lowercase and strip spaces as the Super Admin types.
                const cleaned = input.value.toLowerCase().replace(/\s+/g, '');
                if (cleaned !== input.value) input.value = cleaned;

                clearTimeout(timers.get(input));
                timers.set(input, setTimeout(() => check(input), 350));
            });

            document.addEventListener('submit', (event) => {
                const input = event.target.querySelector('[data-username-check]');
                if (input && input.dataset.usernameState === 'bad') {
                    event.preventDefault();
                    input.focus();
                }
            });
        })();
    </script>
@endonce
