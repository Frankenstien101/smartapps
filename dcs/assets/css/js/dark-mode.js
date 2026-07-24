document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('darkToggle');
    if (toggle) {
        // Load saved preference
        if (localStorage.getItem('darkMode') === 'enabled') {
            document.body.classList.add('dark-mode');
            // Update icon
            const icon = toggle.querySelector('i');
            if (icon) {
                icon.className = 'bi bi-sun-fill';
            }
        }
        toggle.addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');
            localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
            // Update icon
            const icon = toggle.querySelector('i');
            if (icon) {
                icon.className = isDark ? 'bi bi-sun-fill' : 'bi bi-moon';
            }
        });
    }
});