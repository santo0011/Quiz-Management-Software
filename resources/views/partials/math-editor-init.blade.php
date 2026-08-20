<script>
    (function () {
        // Initialize all math input wrappers
        function initMathInput(wrap) {
            if (!wrap || wrap.dataset.initialized === 'true') return;

            const textarea = wrap.querySelector('[data-math-textarea]');
            const toggleBtn = wrap.querySelector('[data-math-toggle]');
            const toolbar = wrap.querySelector('[data-math-toolbar]');
            const closeBtn = wrap.querySelector('[data-math-close]');

            if (!textarea || !toggleBtn || !toolbar) return;

            // Toggle toolbar visibility
            toggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const isHidden = toolbar.hidden;
                // Close all other toolbars first
                document.querySelectorAll('[data-math-toolbar]').forEach((t) => {
                    if (t !== toolbar) t.hidden = true;
                });
                toolbar.hidden = !isHidden;
                if (!isHidden) textarea.focus();
            });

            // Close button
            closeBtn?.addEventListener('click', () => {
                toolbar.hidden = true;
                textarea.focus();
            });

            // Close when clicking outside
            document.addEventListener('click', (e) => {
                if (!wrap.contains(e.target)) {
                    toolbar.hidden = true;
                }
            });

            // Insert math symbol at cursor position
            toolbar.querySelectorAll('[data-math-insert]').forEach((btn) => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    const latex = btn.dataset.mathInsert;
                    const start = textarea.selectionStart;
                    const end = textarea.selectionEnd;
                    const value = textarea.value;
                    textarea.value = value.slice(0, start) + latex + value.slice(end);
                    textarea.selectionStart = textarea.selectionEnd = start + latex.length;
                    textarea.focus();
                    textarea.dispatchEvent(new Event('input', { bubbles: true }));
                });
            });

            wrap.dataset.initialized = 'true';
        }

        // Initialize all existing math inputs
        function initAllMathInputs() {
            document.querySelectorAll('[data-math-input-wrap]').forEach(initMathInput);
        }

        // Watch for dynamically added math inputs (multi-question form)
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) {
                        node.querySelectorAll?.('[data-math-input-wrap]').forEach(initMathInput);
                        if (node.matches?.('[data-math-input-wrap]')) {
                            initMathInput(node);
                        }
                    }
                });
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });

        // Initialize on DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initAllMathInputs);
        } else {
            initAllMathInputs();
        }
    })();
</script>