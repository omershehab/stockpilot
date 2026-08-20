<script>
    (() => {
        const savedTheme = localStorage.getItem('stockpilot-theme');
        const colorPreference = window.matchMedia('(prefers-color-scheme: dark)');
        const prefersDark = colorPreference.matches;
        const dark = savedTheme === 'dark' || (!savedTheme && prefersDark);
        document.documentElement.classList.toggle('dark', dark);
        document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
        colorPreference.addEventListener('change', event => {
            if (localStorage.getItem('stockpilot-theme')) return;
            document.documentElement.classList.toggle('dark', event.matches);
            document.documentElement.style.colorScheme = event.matches ? 'dark' : 'light';
        });
    })();
</script>
