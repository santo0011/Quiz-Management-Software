<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Branch Panel') - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body>
    <div class="admin-shell">
        @include('partials.branch-sidebar')

        <div class="admin-main">
            <header class="admin-topbar">
                <button class="btn btn-icon d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
                    <i class="bi bi-list"></i>
                </button>

                <div>
                    <span class="page-kicker">Branch Panel</span>
                    <h1>@yield('page-title', 'Dashboard')</h1>
                </div>

                <div class="topbar-actions">
                    <div class="admin-user">
                    <div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                    <div class="admin-user-copy">
                        <strong>{{ auth()->user()->name }}</strong>
                        <span>{{ auth()->user()->email }}</span>
                    </div>
                    </div>
                </div>
            </header>

            <main class="admin-content">
                @yield('content')
            </main>
        </div>
    </div>

    <div class="toast-container position-fixed top-0 end-0 p-3">
        @if (session('success'))
            <div class="toast admin-toast text-bg-success border-0" role="status" aria-live="polite" aria-atomic="true" data-bs-delay="3500">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi bi-check-circle-fill"></i>
                        {{ session('success') }}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        @endif
        @if (session('error'))
            <div class="toast admin-toast text-bg-danger border-0" role="status" aria-live="polite" aria-atomic="true" data-bs-delay="4500">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        {{ session('error') }}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        @endif
    </div>

    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content confirm-modal">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="deleteConfirmModalLabel">Confirm Delete</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    This action cannot be undone. Are you sure you want to delete this record?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteButton">
                        <i class="bi bi-trash-fill"></i>
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="toggleStatusModal" tabindex="-1" aria-labelledby="toggleStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content confirm-modal">
                <div class="modal-header">
                    <div>
                        <span class="page-kicker">Status Change</span>
                        <h2 class="modal-title fs-5" id="toggleStatusModalLabel">Confirm Status Change</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="toggle-confirm-icon">
                        <i class="bi bi-arrow-repeat"></i>
                    </div>
                    <p class="mb-0" id="toggleStatusMessage">Are you sure you want to change the status of this record?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmToggleButton">
                        <i class="bi bi-check-circle-fill"></i>
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="publishExamModal" tabindex="-1" aria-labelledby="publishExamModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content confirm-modal">
                <div class="modal-header">
                    <div>
                        <span class="page-kicker">Exam Management</span>
                        <h2 class="modal-title fs-5" id="publishExamModalLabel">Publish Exam</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="publish-confirm-icon">
                        <i class="bi bi-rocket-takeoff-fill"></i>
                    </div>
                    <p class="mb-0">Once this exam is published, it cannot be edited or deleted. Are you sure you want to publish this exam?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmPublishButton">
                        <i class="bi bi-rocket-takeoff-fill"></i>
                        Publish Exam
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="logoutConfirmModal" tabindex="-1" aria-labelledby="logoutConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content confirm-modal logout-modal">
                <div class="modal-header">
                    <div>
                        <span class="page-kicker">Session</span>
                        <h2 class="modal-title fs-5" id="logoutConfirmModalLabel">Logout Confirmation</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="logout-confirm-icon">
                        <i class="bi bi-box-arrow-right"></i>
                    </div>
                    <p class="mb-0">Are you sure you want to logout?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmLogoutButton">
                        <i class="bi bi-box-arrow-right"></i>
                        Logout
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    @include('partials.mobile-tables')
    <script>
        document.querySelectorAll('.admin-toast').forEach((toastEl) => {
            bootstrap.Toast.getOrCreateInstance(toastEl).show();
        });

        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((tooltipEl) => {
            bootstrap.Tooltip.getOrCreateInstance(tooltipEl, {
                boundary: document.body,
                trigger: 'hover focus',
            });
        });

        const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
        const sidebarToggleIcon = sidebarToggle?.querySelector('i');
        const savedSidebarState = localStorage.getItem('quizcore.sidebar.collapsed');

        if (savedSidebarState === 'true') {
            document.body.classList.add('sidebar-collapsed');
            sidebarToggleIcon?.classList.replace('bi-chevron-left', 'bi-chevron-right');
            sidebarToggle?.setAttribute('aria-label', 'Expand sidebar');
            sidebarToggle?.setAttribute('data-bs-title', 'Expand sidebar');
        }

        sidebarToggle?.addEventListener('click', () => {
            const collapsed = document.body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('quizcore.sidebar.collapsed', String(collapsed));
            sidebarToggleIcon.className = collapsed ? 'bi bi-chevron-right' : 'bi bi-chevron-left';
            sidebarToggle.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
            sidebarToggle.setAttribute('data-bs-title', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
            bootstrap.Tooltip.getInstance(sidebarToggle)?.dispose();
            bootstrap.Tooltip.getOrCreateInstance(sidebarToggle, { boundary: document.body });
        });

        document.querySelectorAll('[data-password-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const input = button.closest('.password-field').querySelector('input');
                const icon = button.querySelector('i');
                const showPassword = input.type === 'password';

                input.type = showPassword ? 'text' : 'password';
                button.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
                icon.className = showPassword ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill';
            });
        });

        const deleteModalEl = document.getElementById('deleteConfirmModal');
        const confirmDeleteButton = document.getElementById('confirmDeleteButton');
        let pendingDeleteForm = null;

        document.querySelectorAll('[data-confirm-delete]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                pendingDeleteForm = form;
                bootstrap.Modal.getOrCreateInstance(deleteModalEl).show();
            });
        });

        confirmDeleteButton?.addEventListener('click', () => {
            pendingDeleteForm?.submit();
        });

        const toggleModalEl = document.getElementById('toggleStatusModal');
        const confirmToggleButton = document.getElementById('confirmToggleButton');
        let pendingToggleForm = null;

        document.querySelectorAll('[data-confirm-toggle]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                pendingToggleForm = form;
                const isActive = form.querySelector('button').title.includes('Deactivate');
                document.getElementById('toggleStatusMessage').textContent = isActive
                    ? 'Are you sure you want to deactivate this record? It will no longer be able to log in.'
                    : 'Are you sure you want to activate this record?';
                bootstrap.Modal.getOrCreateInstance(toggleModalEl).show();
            });
        });

        confirmToggleButton?.addEventListener('click', () => {
            pendingToggleForm?.submit();
        });

        const publishModalEl = document.getElementById('publishExamModal');
        const confirmPublishButton = document.getElementById('confirmPublishButton');
        let pendingPublishForm = null;

        document.querySelectorAll('[data-publish-exam]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                pendingPublishForm = form;
                bootstrap.Modal.getOrCreateInstance(publishModalEl).show();
            });
        });

        confirmPublishButton?.addEventListener('click', () => {
            if (! pendingPublishForm) {
                return;
            }

            const form = pendingPublishForm;
            const button = confirmPublishButton;
            const originalHtml = button.innerHTML;

            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Publishing...';

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: new FormData(form),
            })
            .then((response) => {
                return response.json().then((data) => {
                    if (! response.ok) {
                        throw new Error(data.message || 'Publish failed');
                    }
                    return data;
                });
            })
            .then((data) => {
                bootstrap.Modal.getOrCreateInstance(publishModalEl).hide();
                const toastEl = document.createElement('div');
                toastEl.className = 'toast admin-toast text-bg-success border-0';
                toastEl.setAttribute('role', 'status');
                toastEl.setAttribute('aria-live', 'polite');
                toastEl.setAttribute('aria-atomic', 'true');
                toastEl.innerHTML = '<div class="d-flex"><div class="toast-body"><i class="bi bi-check-circle-fill"></i> ' + (data.message || 'Exam published successfully.') + '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>';
                document.querySelector('.toast-container').appendChild(toastEl);
                bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 3500 }).show();
                setTimeout(() => window.location.reload(), 800);
            })
            .catch((error) => {
                button.disabled = false;
                button.innerHTML = originalHtml;
                alert(error.message || 'Failed to publish exam. Please try again.');
            });
        });

        const logoutModalEl = document.getElementById('logoutConfirmModal');
        const confirmLogoutButton = document.getElementById('confirmLogoutButton');
        let pendingLogoutForm = null;

        document.querySelectorAll('[data-logout-form]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                pendingLogoutForm = form;
                bootstrap.Modal.getOrCreateInstance(logoutModalEl).show();
            });
        });

        confirmLogoutButton?.addEventListener('click', () => {
            pendingLogoutForm?.submit();
        });

        @if ($errors->any())
            const preferredDrawerId = @json(old('_drawer'));
            const drawerIds = preferredDrawerId
                ? [preferredDrawerId]
                : ['addStudentDrawer', 'addClassDrawer'];

            drawerIds.some((drawerId) => {
                const drawerEl = document.getElementById(drawerId);
                if (drawerEl) {
                    bootstrap.Offcanvas.getOrCreateInstance(drawerEl).show();
                    return true;
                }

                return false;
            });
        @endif
    </script>
    @include('partials.global-forms')
    @stack('scripts')
</body>
</html>
