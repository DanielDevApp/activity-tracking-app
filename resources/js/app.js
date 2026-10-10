document.addEventListener('DOMContentLoaded', () => {
    const navLinks = document.querySelectorAll('[data-nav-item]');

    navLinks.forEach((link) => {
        link.addEventListener('click', () => {
            navLinks.forEach((item) => {
                item.classList.remove('is-active');
                item.removeAttribute('aria-current');
            });

            link.classList.add('is-active');
            link.setAttribute('aria-current', 'page');
        });
    });

    const toggleButtons = document.querySelectorAll('[data-password-toggle]');

    toggleButtons.forEach((button) => {
        const input = button.closest('.field--password')?.querySelector('input');

        if (!input) {
            return;
        }

        button.addEventListener('click', () => {
            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            button.setAttribute('aria-pressed', String(!showing));
            button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
            button.classList.toggle('is-visible', !showing);
        });
    });
});
