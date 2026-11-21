@php
    $optionValue = (string) ($optionValue ?? '');
    $optionLetter = $optionLetter ?? 'A';
    $optionInvalid = $optionInvalid ?? false;
@endphp

<div class="hybrid-option {{ $optionInvalid ? 'is-invalid' : '' }}" data-option-editor data-option-letter="{{ $optionLetter }}">
    <textarea name="{{ $optionName }}" class="d-none" data-option-value>{{ $optionValue }}</textarea>
    @isset($optionIdName)
        <input type="hidden" name="{{ $optionIdName }}" value="{{ $optionId ?? '' }}" data-option-id>
    @endisset

    <div class="hybrid-option-compact" data-option-compact>
        @php($optionPlain = ! \App\Support\OptionContent::isHtml($optionValue))
        {{-- Plain-text options are typed directly here; rich ones are edited with the Edit button. --}}
        <div class="hybrid-option-preview math-content option-html {{ $optionPlain ? 'tex2jax_ignore' : '' }} {{ \App\Support\OptionContent::isBlank($optionValue) ? 'is-empty' : '' }}"
             data-option-preview
             tabindex="0"
             role="textbox"
             @if ($optionPlain) contenteditable="plaintext-only" @else aria-readonly="true" @endif
             aria-label="Option {{ $optionLetter }}"
             data-placeholder="Click to write option {{ $optionLetter }}, or paste text / an image">{!! \App\Support\OptionContent::isBlank($optionValue) ? '' : \App\Support\OptionContent::toHtml($optionValue) !!}</div>
        <div class="hybrid-option-tools">
            <button type="button" class="hybrid-option-tool" data-option-action="edit" title="Edit with rich editor" aria-label="Edit option {{ $optionLetter }}"><i class="bi bi-pencil-square"></i></button>
            <button type="button" class="hybrid-option-tool" data-option-action="image" title="Insert image" aria-label="Insert image into option {{ $optionLetter }}"><i class="bi bi-image"></i></button>
            <button type="button" class="hybrid-option-tool" data-option-action="math" title="Insert math equation" aria-label="Insert math equation into option {{ $optionLetter }}"><i class="bi bi-calculator"></i></button>
        </div>
    </div>
</div>

@once
    @push('scripts')
        @include('partials.option-editor-script')
    @endpush
@endonce
