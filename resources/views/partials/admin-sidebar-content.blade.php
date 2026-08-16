<div class="brand">
    <div class="brand-mark">Q</div>
    <div class="brand-copy">
        <strong>QuizCore</strong>
        <span>Management Suite</span>
    </div>
    <button class="sidebar-collapse-toggle d-none d-lg-grid" type="button" aria-label="Collapse sidebar" data-sidebar-toggle data-bs-toggle="tooltip" data-bs-title="Collapse sidebar">
        <i class="bi bi-chevron-left"></i>
    </button>
</div>

<nav class="sidebar-nav">
    @foreach ($links as $link)
        @php($isBranchLocked = ($link['requiresBranch'] ?? false) && ! $hasSelectedBranch)
        @if ($link['route'] && ! $isBranchLocked)
            <a href="{{ route($link['route']) }}" class="{{ request()->routeIs($link['active']) ? 'active' : '' }}" data-bs-toggle="tooltip" data-bs-title="{{ $link['label'] }}">
                <i class="bi {{ $link['icon'] }}"></i>
                <span>{{ $link['label'] }}</span>
            </a>
        @else
            <span class="disabled" role="link" aria-disabled="true" data-bs-toggle="tooltip" data-bs-title="{{ $isBranchLocked ? 'Please select a branch first to manage branch-related data.' : $link['label'] }}">
                <i class="bi {{ $link['icon'] }}"></i>
                <span>{{ $link['label'] }}</span>
            </span>
        @endif
    @endforeach
</nav>

<form method="POST" action="{{ route('logout') }}" class="sidebar-logout" data-logout-form>
    @csrf
    <button class="btn w-100" type="submit" data-bs-toggle="tooltip" data-bs-title="Logout">
        <i class="bi bi-box-arrow-right"></i>
        <span>Logout</span>
    </button>
</form>
