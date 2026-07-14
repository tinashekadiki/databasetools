<script>
    (function () {
        function applyEnterpriseTheme() {
            document.documentElement.setAttribute('data-theme', 'light');
            try {
                localStorage.setItem('theme', 'light');
            } catch (e) {}
        }

        applyEnterpriseTheme();
        document.addEventListener('livewire:navigated', applyEnterpriseTheme);
    })();
</script>
