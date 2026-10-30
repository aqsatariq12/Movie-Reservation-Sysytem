
const THEME_KEY = 'cinebook-theme';

function applyTheme(theme) {
    const selectedTheme = theme === 'light' ? 'light' : 'dark';

    document.documentElement.classList.remove('light', 'dark');
    document.documentElement.classList.add(selectedTheme);

    localStorage.setItem(THEME_KEY, selectedTheme);

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.textContent = selectedTheme === 'dark'
            ? '☀ Light'
            : '☾ Dark';

        button.setAttribute(
            'aria-label',
            `Switch to ${selectedTheme === 'dark' ? 'light' : 'dark'} theme`
        );
    });
}

// Apply saved theme when page loads
applyTheme(localStorage.getItem(THEME_KEY) || 'dark');

// Handle theme button clicks
document.addEventListener('click', function (event) {
    const button = event.target.closest('[data-theme-toggle]');

    if (!button) return;

    const currentTheme = document.documentElement.classList.contains('light')
        ? 'light'
        : 'dark';

    applyTheme(currentTheme === 'dark' ? 'light' : 'dark');
});
