@php
    $links = [
        ['label' => 'Dashboard', 'icon' => 'bi-grid-1x2-fill', 'route' => 'branch.dashboard', 'active' => 'branch.dashboard'],
        ['label' => 'Classes', 'icon' => 'bi-collection-fill', 'route' => 'branch.classes.index', 'active' => 'branch.classes.*'],
        ['label' => 'Students', 'icon' => 'bi-people-fill', 'route' => 'branch.students.index', 'active' => 'branch.students.*'],
        ['label' => 'Question Category', 'icon' => 'bi-tags-fill', 'route' => 'branch.question-categories.index', 'active' => 'branch.question-categories.*'],
        ['label' => 'Exams', 'icon' => 'bi-journal-check', 'route' => 'branch.exams.index', 'active' => 'branch.exams.*'],
        ['label' => 'Results', 'icon' => 'bi-bar-chart-fill', 'route' => 'branch.results.index', 'active' => 'branch.results.*'],
        ['label' => 'Change Password', 'icon' => 'bi-key-fill', 'route' => 'branch.password.edit', 'active' => 'branch.password.*'],
    ];
@endphp

<aside class="admin-sidebar d-none d-lg-flex">
    @include('partials.branch-sidebar-content', ['links' => $links])
</aside>

<div class="offcanvas offcanvas-start mobile-sidebar" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
    <div class="offcanvas-body p-0">
        <button type="button" class="mobile-sidebar-close d-lg-none" data-bs-dismiss="offcanvas" aria-label="Close sidebar">
            <i class="bi bi-x-lg"></i>
        </button>
        @include('partials.branch-sidebar-content', ['links' => $links])
    </div>
</div>
