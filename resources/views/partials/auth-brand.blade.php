@php($appSettings = \App\Models\Setting::query()->first())
@php($siteName = $appSettings?->site_name ?: 'QuizCore')
<div class="login-brand {{ $class ?? '' }}">
    @if ($appSettings?->logo_path)
        <img src="{{ Storage::disk('public')->url($appSettings->logo_path) }}" alt="{{ $siteName }} logo" class="brand-logo">
    @else
        <div class="brand-mark">{{ Str::substr($siteName, 0, 1) }}</div>
    @endif
    <div>
        <strong>{{ $siteName }}</strong>
        <span>{{ $subtitle }}</span>
    </div>
</div>
