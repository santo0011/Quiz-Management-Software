@php
    $mathId = $mathId ?? 'math-' . Str::random(8);
    $mathValue = $mathValue ?? '';
    $mathPlaceholder = $mathPlaceholder ?? '';
    $mathRows = $mathRows ?? 2;
    $mathName = $mathName ?? null;
    $mathRequired = $mathRequired ?? false;
    $mathClass = $mathClass ?? '';
    $mathTextareaClass = $mathTextareaClass ?? '';
    $mathReadonly = $mathReadonly ?? false;
@endphp

<div class="math-input-wrap {{ $mathClass }}" data-math-input-wrap data-math-id="{{ $mathId }}">
    <textarea
        id="{{ $mathId }}"
        @if (! $mathReadonly) name="{{ $mathName }}" @endif
        rows="{{ $mathRows }}"
        class="form-control {{ $mathTextareaClass }}"
        placeholder="{{ $mathPlaceholder }}"
        {{ $mathRequired ? 'required' : '' }}
        @if ($mathReadonly) readonly tabindex="-1" @endif
        data-math-textarea
    >{{ $mathValue }}</textarea>
    @unless ($mathReadonly)
    <button type="button" class="math-toolbar-toggle" data-math-toggle aria-label="Math tools" title="Math tools">
        <i class="bi bi-123"></i>
    </button>
    <div class="math-toolbar-popover" data-math-toolbar hidden>
        <div class="math-toolbar-header">
            <span>Math Tools</span>
            <button type="button" class="btn-close btn-close-sm" data-math-close aria-label="Close"></button>
        </div>
        <div class="math-toolbar-grid">
            @include('partials.math-symbol-buttons')
        </div>
    </div>
    @endunless
</div>