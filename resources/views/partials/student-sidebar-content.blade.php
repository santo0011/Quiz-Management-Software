<div class="brand">
    <div class="brand-mark">S</div>
    <div class="brand-copy">
        <strong>{{ $student?->student_name ?? 'Student Panel' }}</strong>
        <span>{{ $student?->schoolClass?->name ?? $student?->class ?? 'QuizCore Portal' }}</span>
    </div>
    <button class="sidebar-collapse-toggle d-none d-lg-grid" type="button" aria-label="Collapse sidebar" data-sidebar-toggle data-bs-toggle="tooltip" data-bs-title="Collapse sidebar">
        <i class="bi bi-chevron-left"></i>
    </button>
</div>

<nav class="sidebar-nav">
    @foreach ($links as $link)
        <a href="{{ $link['url'] }}" class="{{ $link['active'] ? 'active' : '' }}" data-bs-toggle="tooltip" data-bs-title="{{ $link['label'] }}">
            <i class="bi {{ $link['icon'] }}"></i>
            <span>{{ $link['label'] }}</span>
        </a>
    @endforeach
</nav>

<form method="POST" action="{{ route('logout') }}" class="sidebar-logout" data-logout-form>
    @csrf
    <button class="btn w-100" type="submit" data-bs-toggle="tooltip" data-bs-title="Logout">
        <i class="bi bi-box-arrow-right"></i>
        <span>Logout</span>
    </button>
</form>
