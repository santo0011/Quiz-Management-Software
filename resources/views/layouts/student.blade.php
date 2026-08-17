<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Student Portal') - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="student-portal-body">
    <div class="admin-shell student-shell">
        @include('partials.student-sidebar')

        <div class="admin-main">
            <header class="admin-topbar student-panel-topbar">
                <button class="btn btn-icon d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
                    <i class="bi bi-list"></i>
                </button>

                <div>
                    <span class="page-kicker">Student Panel</span>
                    <h1>@yield('page-title', 'Dashboard')</h1>
                </div>

                <div class="topbar-actions">
                    <!-- <div class="current-branch-pill">
                        <i class="bi bi-building"></i>
                        <span>{{ auth('student')->user()?->branch?->name ?? 'Branch not assigned' }}</span>
                    </div> -->
                    <div class="admin-user">
                        <div class="avatar">{{ strtoupper(substr(auth('student')->user()?->student_name ?? 'S', 0, 1)) }}</div>
                        <div class="d-none d-sm-block">
                            <strong>{{ auth('student')->user()?->student_name }}</strong>
                            <span>{{ auth('student')->user()?->email }}</span>
                        </div>
                    </div>
                </div>
            </header>

            <main class="admin-content student-content">
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
    <script>
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((tooltipEl) => {
            bootstrap.Tooltip.getOrCreateInstance(tooltipEl, {
                boundary: document.body,
                trigger: 'hover focus',
            });
        });

        const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
        const sidebarToggleIcon = sidebarToggle?.querySelector('i');
        const savedSidebarState = localStorage.getItem('quizcore.student.sidebar.collapsed');

        if (savedSidebarState === 'true') {
            document.body.classList.add('sidebar-collapsed');
            sidebarToggleIcon?.classList.replace('bi-chevron-left', 'bi-chevron-right');
            sidebarToggle?.setAttribute('aria-label', 'Expand sidebar');
            sidebarToggle?.setAttribute('data-bs-title', 'Expand sidebar');
        }

        sidebarToggle?.addEventListener('click', () => {
            const collapsed = document.body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('quizcore.student.sidebar.collapsed', String(collapsed));
            sidebarToggleIcon.className = collapsed ? 'bi bi-chevron-right' : 'bi bi-chevron-left';
            sidebarToggle.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
            sidebarToggle.setAttribute('data-bs-title', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
            bootstrap.Tooltip.getInstance(sidebarToggle)?.dispose();
            bootstrap.Tooltip.getOrCreateInstance(sidebarToggle, { boundary: document.body });
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
    </script>
    @include('partials.global-forms')
    @stack('scripts')
</body>
</html>
