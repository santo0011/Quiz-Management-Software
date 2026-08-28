<script>
    (function () {
        // Global form submission loading state
        // Disables submit buttons and shows loading spinner while the request is in-flight.
        document.addEventListener('submit', (event) => {
            const form = event.target;

            // Skip forms with confirmation modals (they submit on modal confirm)
            if (form.hasAttribute('data-confirm-delete') || form.hasAttribute('data-confirm-toggle') || form.hasAttribute('data-publish-exam') || form.hasAttribute('data-logout-form') || form.hasAttribute('data-confirm-remark')) {
                return;
            }

            // Skip forms that submit via AJAX/JS
            if (form.hasAttribute('data-ajax-submit') || form.hasAttribute('data-ajax-form')) {
                return;
            }

            const submitter = event.submitter;
            const button = submitter && submitter.tagName === 'BUTTON'
                ? submitter
                : form.querySelector('button[type="submit"]:not([data-confirm-delete]):not([data-publish-exam]):not([data-confirm-toggle])');

            if (! button || button.disabled) {
                return;
            }

            const originalHtml = button.innerHTML;
            const label = (button.dataset.loadingText || button.textContent.trim() || 'Submit').trim();
            const loadingText = button.dataset.loading || 'Processing...';

            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            button.setAttribute('data-original-html', originalHtml);
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' + loadingText;

            // If the request fails (e.g., validation errors cause a redirect back), restore the button on next page load.
            // The browser navigation naturally reloads the page so no restoration is needed.
            // For AJAX handlers, they manage their own button states.
        });
    })();
</script>