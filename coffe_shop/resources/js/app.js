document.querySelectorAll('[data-auth-flip]').forEach((link) => {
    link.addEventListener('click', (event) => {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        const shell = link.closest('.auth-shell');
        if (!shell || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        event.preventDefault();
        shell.classList.add('is-flipping');
        window.setTimeout(() => window.location.assign(link.href), 430);
    });
});
