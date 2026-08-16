@php
    $student = auth('student')->user();
    $links = [
        ['label' => 'Dashboard', 'icon' => 'bi-grid-1x2-fill', 'url' => route('student.dashboard'), 'active' => request()->routeIs('student.dashboard')],
        ['label' => 'Available Exams', 'icon' => 'bi-journal-check', 'url' => route('student.dashboard').'#available-exams', 'active' => request()->routeIs('student.exams.*')],
        ['label' => 'My Exams', 'icon' => 'bi-clipboard-check-fill', 'url' => route('student.results.index'), 'active' => false],
        ['label' => 'Results', 'icon' => 'bi-bar-chart-fill', 'url' => route('student.results.index'), 'active' => request()->routeIs('student.results.*')],
        ['label' => 'Profile', 'icon' => 'bi-person-badge-fill', 'url' => route('student.dashboard').'#student-profile', 'active' => false],
    ];
@endphp

<aside class="admin-sidebar student-sidebar d-none d-lg-flex">
    @include('partials.student-sidebar-content', ['student' => $student, 'links' => $links])
</aside>

<div class="offcanvas offcanvas-start mobile-sidebar student-mobile-sidebar" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
    <div class="offcanvas-body p-0">
        <button type="button" class="mobile-sidebar-close d-lg-none" data-bs-dismiss="offcanvas" aria-label="Close sidebar">
            <i class="bi bi-x-lg"></i>
        </button>
        @include('partials.student-sidebar-content', ['student' => $student, 'links' => $links])
    </div>
</div>
