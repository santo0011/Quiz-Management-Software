@php
    $links = [
        ['label' => 'Dashboard', 'icon' => 'bi-grid-1x2-fill', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard'],
        ['label' => 'Branches', 'icon' => 'bi-diagram-3-fill', 'route' => 'admin.branches.index', 'active' => 'admin.branches.*'],
        ['label' => 'Classes', 'icon' => 'bi-collection-fill', 'route' => 'admin.classes.index', 'active' => 'admin.classes.*'],
        ['label' => 'Students', 'icon' => 'bi-people-fill', 'route' => 'admin.students.index', 'active' => 'admin.students.*'],
        ['label' => 'Exams', 'icon' => 'bi-journal-check', 'route' => 'admin.exams.index', 'active' => 'admin.exams.*'],
        ['label' => 'Results', 'icon' => 'bi-bar-chart-fill', 'route' => 'admin.results.index', 'active' => 'admin.results.*'],
        ['label' => 'Change Password', 'icon' => 'bi-key-fill', 'route' => 'admin.password.edit', 'active' => 'admin.password.*'],
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
