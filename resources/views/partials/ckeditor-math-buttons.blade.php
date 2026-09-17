{{--
    Symbol/template grid for the CKEditor "Insert Math Equation" tool. Buttons
    insert into the staging input (see ckeditor-math-wrap script in
    summary-editor.blade.php) — the whole staged expression is wrapped in a
    single \( \) pair only once, when "Insert Equation" is clicked.

    Two kinds of buttons:
    - Structural ones (fraction, superscript, sqrt, sum/integral with limits,
      overline, vector arrow, cases/matrix, sized brackets) insert real LaTeX
      commands, since they need MathJax to actually build the shape (a
      fraction bar, a rendered root sign, etc.) — there's no plain-character
      equivalent for those.
    - Everything else (Greek letters, comparison/set/logic symbols) inserts
      the literal Unicode character directly rather than a LaTeX command like
      "\pi". MathJax renders either one identically once the equation is
      saved, but a bare Unicode character also displays correctly while
      still editing/staging — unlike "\pi", which just sits there as raw,
      unrendered text ("\pi") until then. Confirmed via MathJax's own output
      that a literal Greek letter typesets exactly the same as the LaTeX
      command form.

    This list mirrors the button set used for dynamically-added Answer
    Option rows (questions/partials/multi-form.blade.php's
    MATH_TOOLBAR_BUTTONS_HTML) so both tools offer the same symbols.
--}}
<button type="button" class="math-tool-btn" data-math-insert="\frac{}{}" title="Fraction">a/b</button>
<button type="button" class="math-tool-btn" data-math-insert="^{}" title="Superscript">x²</button>
<button type="button" class="math-tool-btn" data-math-insert="_{}" title="Subscript">x₂</button>
<button type="button" class="math-tool-btn" data-math-insert="\sqrt{}" title="Square Root">√</button>
<button type="button" class="math-tool-btn" data-math-insert="π" title="Pi">π</button>
<button type="button" class="math-tool-btn" data-math-insert="α" title="Alpha">α</button>
<button type="button" class="math-tool-btn" data-math-insert="β" title="Beta">β</button>
<button type="button" class="math-tool-btn" data-math-insert="θ" title="Theta">θ</button>
<button type="button" class="math-tool-btn" data-math-insert="±" title="±">±</button>
<button type="button" class="math-tool-btn" data-math-insert="×" title="×">×</button>
<button type="button" class="math-tool-btn" data-math-insert="÷" title="÷">÷</button>
<button type="button" class="math-tool-btn" data-math-insert="≤" title="≤">≤</button>
<button type="button" class="math-tool-btn" data-math-insert="≥" title="≥">≥</button>
<button type="button" class="math-tool-btn" data-math-insert="≠" title="≠">≠</button>
<button type="button" class="math-tool-btn" data-math-insert="∞" title="∞">∞</button>
<button type="button" class="math-tool-btn" data-math-insert="\sum_{}^{}" title="Σ">Σ</button>
<button type="button" class="math-tool-btn" data-math-insert="\int_{}^{}" title="∫">∫</button>
<button type="button" class="math-tool-btn" data-math-insert="→" title="→">→</button>
<button type="button" class="math-tool-btn" data-math-insert="\left( \right)" title="( )">( )</button>
<button type="button" class="math-tool-btn" data-math-insert="\left[ \right]" title="[ ]">[ ]</button>
<button type="button" class="math-tool-btn" data-math-insert="\left\{ \right\}" title="{ }">{ }</button>
<button type="button" class="math-tool-btn" data-math-insert="·" title="·">·</button>
<button type="button" class="math-tool-btn" data-math-insert="Δ" title="Δ">Δ</button>
<button type="button" class="math-tool-btn" data-math-insert="λ" title="λ">λ</button>
<button type="button" class="math-tool-btn" data-math-insert="μ" title="μ">μ</button>
<button type="button" class="math-tool-btn" data-math-insert="σ" title="σ">σ</button>
<button type="button" class="math-tool-btn" data-math-insert="ω" title="ω">ω</button>
<button type="button" class="math-tool-btn" data-math-insert="°" title="°">°</button>
<button type="button" class="math-tool-btn" data-math-insert="∠" title="∠">∠</button>
<button type="button" class="math-tool-btn" data-math-insert="⊥" title="⊥">⊥</button>
<button type="button" class="math-tool-btn" data-math-insert="∥" title="∥">∥</button>
<button type="button" class="math-tool-btn" data-math-insert="≅" title="≅">≅</button>
<button type="button" class="math-tool-btn" data-math-insert="∼" title="∼">∼</button>
<button type="button" class="math-tool-btn" data-math-insert="∈" title="∈">∈</button>
<button type="button" class="math-tool-btn" data-math-insert="∉" title="∉">∉</button>
<button type="button" class="math-tool-btn" data-math-insert="⊂" title="⊂">⊂</button>
<button type="button" class="math-tool-btn" data-math-insert="∪" title="∪">∪</button>
<button type="button" class="math-tool-btn" data-math-insert="∩" title="∩">∩</button>
<button type="button" class="math-tool-btn" data-math-insert="∅" title="∅">∅</button>
<button type="button" class="math-tool-btn" data-math-insert="∴" title="∴">∴</button>
<button type="button" class="math-tool-btn" data-math-insert="∵" title="∵">∵</button>
<button type="button" class="math-tool-btn" data-math-insert="∀" title="∀">∀</button>
<button type="button" class="math-tool-btn" data-math-insert="∃" title="∃">∃</button>
<button type="button" class="math-tool-btn" data-math-insert="¬" title="¬">¬</button>
<button type="button" class="math-tool-btn" data-math-insert="∧" title="∧">∧</button>
<button type="button" class="math-tool-btn" data-math-insert="∨" title="∨">∨</button>
<button type="button" class="math-tool-btn" data-math-insert="⇒" title="⇒">⇒</button>
<button type="button" class="math-tool-btn" data-math-insert="⇔" title="⇔">⇔</button>
<button type="button" class="math-tool-btn" data-math-insert="\overline{}" title="‾">‾</button>
<button type="button" class="math-tool-btn" data-math-insert="\overrightarrow{}" title="→">→</button>
<button type="button" class="math-tool-btn" data-math-insert="\begin{cases} & \\ & \end{cases}" title="{ }">{ }</button>
<button type="button" class="math-tool-btn" data-math-insert="\begin{matrix} & \\ & \end{matrix}" title="[ ]">[ ]</button>
