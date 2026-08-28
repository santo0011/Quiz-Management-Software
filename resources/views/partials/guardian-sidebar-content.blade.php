<div class="brand">
    <div class="brand-mark">G</div>
    <div class="brand-copy">
        <strong>Guardian Panel</strong>
        <span>{{ $guardian?->email }}</span>
    </div>
    <button class="sidebar-collapse-toggle d-none d-lg-grid" type="button" aria-label="Collapse sidebar" data-sidebar-toggle>
        <i class="bi bi-chevron-left"></i>
    </button>
</div>

<nav class="sidebar-nav">
    @foreach ($links as $link)
        <a href="{{ $link['url'] }}" class="{{ $link['active'] ? 'active' : '' }}">
            <i class="bi {{ $link['icon'] }}"></i>
            <span>{{ $link['label'] }}</span>
        </a>
    @endforeach
</nav>

<form method="POST" action="{{ route('logout') }}" class="sidebar-logout" data-logout-form>
    @csrf
    <button class="btn w-100" type="submit">
        <i class="bi bi-box-arrow-right"></i>
        <span>Logout</span>
    </button>
</form>
