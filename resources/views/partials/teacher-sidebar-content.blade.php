@php($appSettings = \App\Models\Setting::query()->first())

<div class="brand">
    @if ($appSettings?->logo_path)
        <img src="{{ Storage::disk('public')->url($appSettings->logo_path) }}" alt="Logo" class="brand-logo">
    @else
        <div class="brand-mark">B</div>
    @endif
    <div class="brand-copy">
        <strong>{{ $teacher?->branch?->name ?? 'Teacher Panel' }}</strong>
        <span>{{ $appSettings?->site_name ?: 'QuizCore' }} Workspace</span>
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
