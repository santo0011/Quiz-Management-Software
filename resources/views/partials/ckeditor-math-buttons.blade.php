{{--
    Symbol/template grid for the CKEditor "Insert Math Equation" tool. Buttons
    insert bare LaTeX into the staging input (see ckeditor-math-wrap script in
    summary-editor.blade.php) — the whole staged expression is wrapped in a
    single \( \) pair only once, when "Insert Equation" is clicked. This list
    mirrors the LaTeX button set already used for dynamically-added Answer
    Option rows (questions/partials/multi-form.blade.php's
    MATH_TOOLBAR_BUTTONS_HTML) so both tools offer the same symbols.
--}}
<button type="button" class="math-tool-btn" data-math-insert="\frac{}{}" title="Fraction">a/b</button>
<button type="button" class="math-tool-btn" data-math-insert="^{}" title="Superscript">x²</button>
<button type="button" class="math-tool-btn" data-math-insert="_{}" title="Subscript">x₂</button>
<button type="button" class="math-tool-btn" data-math-insert="\sqrt{}" title="Square Root">√</button>
<button type="button" class="math-tool-btn" data-math-insert="\pi" title="Pi">π</button>
<button type="button" class="math-tool-btn" data-math-insert="\alpha" title="Alpha">α</button>
<button type="button" class="math-tool-btn" data-math-insert="\beta" title="Beta">β</button>
<button type="button" class="math-tool-btn" data-math-insert="\theta" title="Theta">θ</button>
<button type="button" class="math-tool-btn" data-math-insert="\pm" title="±">±</button>
<button type="button" class="math-tool-btn" data-math-insert="\times" title="×">×</button>
<button type="button" class="math-tool-btn" data-math-insert="\div" title="÷">÷</button>
<button type="button" class="math-tool-btn" data-math-insert="\leq" title="≤">≤</button>
<button type="button" class="math-tool-btn" data-math-insert="\geq" title="≥">≥</button>
<button type="button" class="math-tool-btn" data-math-insert="\neq" title="≠">≠</button>
<button type="button" class="math-tool-btn" data-math-insert="\infty" title="∞">∞</button>
<button type="button" class="math-tool-btn" data-math-insert="\sum_{}^{}" title="Σ">Σ</button>
<button type="button" class="math-tool-btn" data-math-insert="\int_{}^{}" title="∫">∫</button>
<button type="button" class="math-tool-btn" data-math-insert="\rightarrow" title="→">→</button>
<button type="button" class="math-tool-btn" data-math-insert="\left( \right)" title="( )">( )</button>
<button type="button" class="math-tool-btn" data-math-insert="\left[ \right]" title="[ ]">[ ]</button>
<button type="button" class="math-tool-btn" data-math-insert="\left\{ \right\}" title="{ }">{ }</button>
<button type="button" class="math-tool-btn" data-math-insert="\cdot" title="·">·</button>
<button type="button" class="math-tool-btn" data-math-insert="\Delta" title="Δ">Δ</button>
<button type="button" class="math-tool-btn" data-math-insert="\lambda" title="λ">λ</button>
<button type="button" class="math-tool-btn" data-math-insert="\mu" title="μ">μ</button>
<button type="button" class="math-tool-btn" data-math-insert="\sigma" title="σ">σ</button>
<button type="button" class="math-tool-btn" data-math-insert="\omega" title="ω">ω</button>
<button type="button" class="math-tool-btn" data-math-insert="\degree" title="°">°</button>
<button type="button" class="math-tool-btn" data-math-insert="\angle" title="∠">∠</button>
<button type="button" class="math-tool-btn" data-math-insert="\perp" title="⊥">⊥</button>
<button type="button" class="math-tool-btn" data-math-insert="\parallel" title="∥">∥</button>
<button type="button" class="math-tool-btn" data-math-insert="\cong" title="≅">≅</button>
<button type="button" class="math-tool-btn" data-math-insert="\sim" title="∼">∼</button>
<button type="button" class="math-tool-btn" data-math-insert="\in" title="∈">∈</button>
<button type="button" class="math-tool-btn" data-math-insert="\notin" title="∉">∉</button>
<button type="button" class="math-tool-btn" data-math-insert="\subset" title="⊂">⊂</button>
<button type="button" class="math-tool-btn" data-math-insert="\cup" title="∪">∪</button>
<button type="button" class="math-tool-btn" data-math-insert="\cap" title="∩">∩</button>
<button type="button" class="math-tool-btn" data-math-insert="\emptyset" title="∅">∅</button>
<button type="button" class="math-tool-btn" data-math-insert="\therefore" title="∴">∴</button>
<button type="button" class="math-tool-btn" data-math-insert="\because" title="∵">∵</button>
<button type="button" class="math-tool-btn" data-math-insert="\forall" title="∀">∀</button>
<button type="button" class="math-tool-btn" data-math-insert="\exists" title="∃">∃</button>
<button type="button" class="math-tool-btn" data-math-insert="\neg" title="¬">¬</button>
<button type="button" class="math-tool-btn" data-math-insert="\land" title="∧">∧</button>
<button type="button" class="math-tool-btn" data-math-insert="\lor" title="∨">∨</button>
<button type="button" class="math-tool-btn" data-math-insert="\implies" title="⇒">⇒</button>
<button type="button" class="math-tool-btn" data-math-insert="\iff" title="⇔">⇔</button>
<button type="button" class="math-tool-btn" data-math-insert="\overline{}" title="‾">‾</button>
<button type="button" class="math-tool-btn" data-math-insert="\overrightarrow{}" title="→">→</button>
<button type="button" class="math-tool-btn" data-math-insert="\begin{cases} & \\ & \end{cases}" title="{ }">{ }</button>
<button type="button" class="math-tool-btn" data-math-insert="\begin{matrix} & \\ & \end{matrix}" title="[ ]">[ ]</button>
