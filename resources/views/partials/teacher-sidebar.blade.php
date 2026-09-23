@php
    $teacher = auth('teacher')->user();
    $links = [
        ['label' => 'Dashboard', 'icon' => 'bi-grid-1x2-fill', 'url' => route('teacher.dashboard'), 'active' => request()->routeIs('teacher.dashboard')],
        ['label' => 'Exams', 'icon' => 'bi-journal-check', 'url' => route('teacher.exams.index'), 'active' => request()->routeIs('teacher.exams.*') || request()->routeIs('teacher.questions.*') || request()->routeIs('teacher.passage-groups.*') || request()->routeIs('teacher.question-categories.*')],
        ['label' => 'Results', 'icon' => 'bi-bar-chart-fill', 'url' => route('teacher.results.index'), 'active' => request()->routeIs('teacher.results.*')],
        ['label' => 'Change Password', 'icon' => 'bi-shield-lock-fill', 'url' => route('teacher.password.edit'), 'active' => request()->routeIs('teacher.password.*')],
    ];
@endphp

<aside class="admin-sidebar student-sidebar d-none d-lg-flex">
    @include('partials.teacher-sidebar-content', ['teacher' => $teacher, 'links' => $links])
</aside>

<div class="offcanvas offcanvas-start mobile-sidebar student-mobile-sidebar" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
    <div class="offcanvas-body p-0">
        <button type="button" class="mobile-sidebar-close d-lg-none" data-bs-dismiss="offcanvas" aria-label="Close sidebar">
            <i class="bi bi-x-lg"></i>
        </button>
        @include('partials.teacher-sidebar-content', ['teacher' => $teacher, 'links' => $links])
    </div>
</div>
