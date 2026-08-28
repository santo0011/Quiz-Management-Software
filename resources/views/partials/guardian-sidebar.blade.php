@php
    $guardian = auth('guardian')->user();
    $links = [
        ['label' => 'My Students', 'icon' => 'bi-people-fill', 'url' => route('guardian.dashboard'), 'active' => request()->routeIs('guardian.dashboard') || request()->routeIs('guardian.students.*')],
        ['label' => 'Profile', 'icon' => 'bi-person-circle', 'url' => route('guardian.profile'), 'active' => request()->routeIs('guardian.profile')],
        ['label' => 'Change Password', 'icon' => 'bi-shield-lock-fill', 'url' => route('guardian.password.edit'), 'active' => request()->routeIs('guardian.password.*')],
    ];
@endphp

<aside class="admin-sidebar student-sidebar d-none d-lg-flex">
    @include('partials.guardian-sidebar-content', ['guardian' => $guardian, 'links' => $links])
</aside>

<div class="offcanvas offcanvas-start mobile-sidebar student-mobile-sidebar" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
    <div class="offcanvas-body p-0">
        <button type="button" class="mobile-sidebar-close d-lg-none" data-bs-dismiss="offcanvas" aria-label="Close sidebar">
            <i class="bi bi-x-lg"></i>
        </button>
        @include('partials.guardian-sidebar-content', ['guardian' => $guardian, 'links' => $links])
    </div>
</div>
