<script>
    // Prevent the mouse wheel from changing the value of any number input,
    // including ones added to the page dynamically after load.
    document.addEventListener('wheel', function (event) {
        if (document.activeElement && document.activeElement.type === 'number') {
            document.activeElement.blur();
        }
    }, { passive: true });
</script>
