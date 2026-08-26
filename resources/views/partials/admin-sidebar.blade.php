@php
    $links = [
        ['label' => 'Dashboard', 'icon' => 'bi-grid-1x2-fill', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard'],
        ['label' => 'Academic Sessions', 'icon' => 'bi-calendar-range', 'route' => 'admin.academic-sessions.index', 'active' => 'admin.academic-sessions.*'],
        ['label' => 'Branches', 'icon' => 'bi-diagram-3-fill', 'route' => 'admin.branches.index', 'active' => 'admin.branches.*'],
        ['label' => 'Grades', 'icon' => 'bi-collection-fill', 'route' => 'admin.classes.index', 'active' => 'admin.classes.*'],
        ['label' => 'Subjects', 'icon' => 'bi-book-fill', 'route' => 'admin.subjects.index', 'active' => 'admin.subjects.*'],
        ['label' => 'Students', 'icon' => 'bi-people-fill', 'route' => 'admin.students.index', 'active' => 'admin.students.*'],
        ['label' => 'Question Category', 'icon' => 'bi-tags-fill', 'route' => 'admin.question-categories.index', 'active' => 'admin.question-categories.*'],
        ['label' => 'Exams', 'icon' => 'bi-journal-check', 'route' => 'admin.exams.index', 'active' => 'admin.exams.*'],
        ['label' => 'Results', 'icon' => 'bi-bar-chart-fill', 'route' => 'admin.results.index', 'active' => 'admin.results.*'],
        ['label' => 'Settings', 'icon' => 'bi-gear-fill', 'route' => 'admin.settings.edit', 'active' => 'admin.settings.*'],
    ];
@endphp

<aside class="admin-sidebar d-none d-lg-flex">
    @include('partials.admin-sidebar-content', ['links' => $links])
</aside>

<div class="offcanvas offcanvas-start mobile-sidebar" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
    <div class="offcanvas-body p-0">
        <button type="button" class="mobile-sidebar-close d-lg-none" data-bs-dismiss="offcanvas" aria-label="Close sidebar">
            <i class="bi bi-x-lg"></i>
        </button>
        @include('partials.admin-sidebar-content', ['links' => $links])
    </div>
</div>
