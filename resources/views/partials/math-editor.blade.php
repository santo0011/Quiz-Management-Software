@php
    $mathId = $mathId ?? 'math-' . Str::random(8);
    $mathValue = $mathValue ?? '';
    $mathPlaceholder = $mathPlaceholder ?? '';
    $mathRows = $mathRows ?? 2;
    $mathName = $mathName ?? null;
    $mathRequired = $mathRequired ?? false;
    $mathClass = $mathClass ?? '';
    $mathTextareaClass = $mathTextareaClass ?? '';
@endphp

<div class="math-input-wrap {{ $mathClass }}" data-math-input-wrap data-math-id="{{ $mathId }}">
    <textarea
        id="{{ $mathId }}"
        name="{{ $mathName }}"
        rows="{{ $mathRows }}"
        class="form-control {{ $mathTextareaClass }}"
        placeholder="{{ $mathPlaceholder }}"
        {{ $mathRequired ? 'required' : '' }}
        data-math-textarea
    >{{ $mathValue }}</textarea>
    <button type="button" class="math-toolbar-toggle" data-math-toggle aria-label="Math tools" title="Math tools">
        <i class="bi bi-123"></i>
    </button>
    <div class="math-toolbar-popover" data-math-toolbar hidden>
        <div class="math-toolbar-header">
            <span>Math Tools</span>
            <button type="button" class="btn-close btn-close-sm" data-math-close aria-label="Close"></button>
        </div>
        <div class="math-toolbar-grid">
            <button type="button" class="math-tool-btn" data-math-insert="π" title="Pi">π</button>
            <button type="button" class="math-tool-btn" data-math-insert="√" title="Square Root">√</button>
            <button type="button" class="math-tool-btn" data-math-insert="²" title="Superscript 2">x²</button>
            <button type="button" class="math-tool-btn" data-math-insert="³" title="Superscript 3">x³</button>
            <button type="button" class="math-tool-btn" data-math-insert="⁴" title="Superscript 4">x⁴</button>
            <button type="button" class="math-tool-btn" data-math-insert="ⁿ" title="Superscript n">xⁿ</button>
            <button type="button" class="math-tool-btn" data-math-insert="₀" title="Subscript 0">x₀</button>
            <button type="button" class="math-tool-btn" data-math-insert="₁" title="Subscript 1">x₁</button>
            <button type="button" class="math-tool-btn" data-math-insert="₂" title="Subscript 2">x₂</button>
            <button type="button" class="math-tool-btn" data-math-insert="ₙ" title="Subscript n">xₙ</button>
            <button type="button" class="math-tool-btn" data-math-insert="½" title="Half">½</button>
            <button type="button" class="math-tool-btn" data-math-insert="⅓" title="Third">⅓</button>
            <button type="button" class="math-tool-btn" data-math-insert="¼" title="Quarter">¼</button>
            <button type="button" class="math-tool-btn" data-math-insert="¾" title="Three quarters">¾</button>
            <button type="button" class="math-tool-btn" data-math-insert="α" title="Alpha">α</button>
            <button type="button" class="math-tool-btn" data-math-insert="β" title="Beta">β</button>
            <button type="button" class="math-tool-btn" data-math-insert="γ" title="Gamma">γ</button>
            <button type="button" class="math-tool-btn" data-math-insert="δ" title="Delta">δ</button>
            <button type="button" class="math-tool-btn" data-math-insert="θ" title="Theta">θ</button>
            <button type="button" class="math-tool-btn" data-math-insert="λ" title="Lambda">λ</button>
            <button type="button" class="math-tool-btn" data-math-insert="μ" title="Mu">μ</button>
            <button type="button" class="math-tool-btn" data-math-insert="σ" title="Sigma">σ</button>
            <button type="button" class="math-tool-btn" data-math-insert="ω" title="Omega">ω</button>
            <button type="button" class="math-tool-btn" data-math-insert="Δ" title="Capital Delta">Δ</button>
            <button type="button" class="math-tool-btn" data-math-insert="Σ" title="Summation">Σ</button>
            <button type="button" class="math-tool-btn" data-math-insert="∫" title="Integral">∫</button>
            <button type="button" class="math-tool-btn" data-math-insert="±" title="Plus/Minus">±</button>
            <button type="button" class="math-tool-btn" data-math-insert="×" title="Multiply">×</button>
            <button type="button" class="math-tool-btn" data-math-insert="÷" title="Divide">÷</button>
            <button type="button" class="math-tool-btn" data-math-insert="≤" title="Less than or equal">≤</button>
            <button type="button" class="math-tool-btn" data-math-insert="≥" title="Greater than or equal">≥</button>
            <button type="button" class="math-tool-btn" data-math-insert="≠" title="Not equal">≠</button>
            <button type="button" class="math-tool-btn" data-math-insert="≈" title="Approximately">≈</button>
            <button type="button" class="math-tool-btn" data-math-insert="∞" title="Infinity">∞</button>
            <button type="button" class="math-tool-btn" data-math-insert="→" title="Right arrow">→</button>
            <button type="button" class="math-tool-btn" data-math-insert="←" title="Left arrow">←</button>
            <button type="button" class="math-tool-btn" data-math-insert="↔" title="Left-right arrow">↔</button>
            <button type="button" class="math-tool-btn" data-math-insert="(" title="Left bracket">(</button>
            <button type="button" class="math-tool-btn" data-math-insert=")" title="Right bracket">)</button>
            <button type="button" class="math-tool-btn" data-math-insert="[" title="Left square bracket">[</button>
            <button type="button" class="math-tool-btn" data-math-insert="]" title="Right square bracket">]</button>
            <button type="button" class="math-tool-btn" data-math-insert="{" title="Left curly bracket">{</button>
            <button type="button" class="math-tool-btn" data-math-insert="}" title="Right curly bracket">}</button>
            <button type="button" class="math-tool-btn" data-math-insert="·" title="Dot">·</button>
            <button type="button" class="math-tool-btn" data-math-insert="°" title="Degree">°</button>
            <button type="button" class="math-tool-btn" data-math-insert="∠" title="Angle">∠</button>
            <button type="button" class="math-tool-btn" data-math-insert="⊥" title="Perpendicular">⊥</button>
            <button type="button" class="math-tool-btn" data-math-insert="∥" title="Parallel">∥</button>
            <button type="button" class="math-tool-btn" data-math-insert="≅" title="Congruent">≅</button>
            <button type="button" class="math-tool-btn" data-math-insert="∼" title="Similar">∼</button>
            <button type="button" class="math-tool-btn" data-math-insert="∝" title="Proportional">∝</button>
            <button type="button" class="math-tool-btn" data-math-insert="∈" title="Element of">∈</button>
            <button type="button" class="math-tool-btn" data-math-insert="∉" title="Not element of">∉</button>
            <button type="button" class="math-tool-btn" data-math-insert="⊂" title="Subset">⊂</button>
            <button type="button" class="math-tool-btn" data-math-insert="∪" title="Union">∪</button>
            <button type="button" class="math-tool-btn" data-math-insert="∩" title="Intersection">∩</button>
            <button type="button" class="math-tool-btn" data-math-insert="∅" title="Empty set">∅</button>
            <button type="button" class="math-tool-btn" data-math-insert="∴" title="Therefore">∴</button>
            <button type="button" class="math-tool-btn" data-math-insert="∵" title="Because">∵</button>
            <button type="button" class="math-tool-btn" data-math-insert="∀" title="For all">∀</button>
            <button type="button" class="math-tool-btn" data-math-insert="∃" title="There exists">∃</button>
            <button type="button" class="math-tool-btn" data-math-insert="¬" title="Negation">¬</button>
            <button type="button" class="math-tool-btn" data-math-insert="∧" title="And">∧</button>
            <button type="button" class="math-tool-btn" data-math-insert="∨" title="Or">∨</button>
            <button type="button" class="math-tool-btn" data-math-insert="⇒" title="Implies">⇒</button>
            <button type="button" class="math-tool-btn" data-math-insert="⇔" title="If and only if">⇔</button>
            <button type="button" class="math-tool-btn" data-math-insert="=" title="Equals">=</button>
            <button type="button" class="math-tool-btn" data-math-insert="<" title="Less than"><</button>
            <button type="button" class="math-tool-btn" data-math-insert=">" title="Greater than">></button>
            <button type="button" class="math-tool-btn" data-math-insert="%" title="Percent">%</button>
            <button type="button" class="math-tool-btn" data-math-insert="‰" title="Per mille">‰</button>
            <button type="button" class="math-tool-btn" data-math-insert="√" title="Square root">√</button>
            <button type="button" class="math-tool-btn" data-math-insert="∛" title="Cube root">∛</button>
            <button type="button" class="math-tool-btn" data-math-insert="∜" title="Fourth root">∜</button>
            <button type="button" class="math-tool-btn" data-math-insert="∑" title="Summation">∑</button>
            <button type="button" class="math-tool-btn" data-math-insert="∏" title="Product">∏</button>
            <button type="button" class="math-tool-btn" data-math-insert="∂" title="Partial derivative">∂</button>
            <button type="button" class="math-tool-btn" data-math-insert="∇" title="Nabla">∇</button>
            <button type="button" class="math-tool-btn" data-math-insert="√" title="Radical">√</button>
            <button type="button" class="math-tool-btn" data-math-insert="∞" title="Infinity">∞</button>
            <button type="button" class="math-tool-btn" data-math-insert="ℕ" title="Natural numbers">ℕ</button>
            <button type="button" class="math-tool-btn" data-math-insert="ℤ" title="Integers">ℤ</button>
            <button type="button" class="math-tool-btn" data-math-insert="ℚ" title="Rationals">ℚ</button>
            <button type="button" class="math-tool-btn" data-math-insert="ℝ" title="Reals">ℝ</button>
            <button type="button" class="math-tool-btn" data-math-insert="ℂ" title="Complex">ℂ</button>
        </div>
    </div>
</div>