<script>
    (function () {
        const picker = document.getElementById('defaultCategoryPicker');
        if (! picker) {
            return;
        }

        const select = document.getElementById('defaultCategorySelect');
        const confirmBtn = document.getElementById('confirmDefaultCategory');
        const confirmedBox = document.getElementById('defaultCategoryConfirmed');
        const confirmedName = document.getElementById('defaultCategoryName');

        select.addEventListener('change', () => {
            confirmBtn.disabled = ! select.value;
        });

        confirmBtn.addEventListener('click', () => {
            if (! select.value) {
                return;
            }

            // Read live by every multi-question form's "Add Another Question"
            // clone script (window.defaultQuestionCategoryId) so newly created
            // question rows — anywhere on the page — start pre-set to this
            // category, without touching rows a user already changed.
            window.defaultQuestionCategoryId = select.value;

            document.querySelectorAll('select[name*="[question_category_id]"]').forEach((categorySelect) => {
                if (! categorySelect.value) {
                    categorySelect.value = select.value;
                }
            });

            confirmedName.textContent = select.options[select.selectedIndex].textContent;
            confirmedBox.classList.remove('d-none');
        });
    })();
</script>
