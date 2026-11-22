/* Put in <head> (no defer) so there is no "flash" of the wrong colors. */
(function () {
    var root = document.documentElement;
    var saved = null;
    try { saved = localStorage.getItem('theme'); } catch (e) {}
    if (saved === 'light' || saved === 'dark') root.setAttribute('data-theme', saved);

    var mq = window.matchMedia('(prefers-color-scheme: dark)');
    function current() { return root.getAttribute('data-theme') || (mq.matches ? 'dark' : 'light'); }

    function updateButton() {
        var b = document.getElementById('themeBtn');
        if (!b) return;
        var dark = current() === 'dark';
        b.textContent = dark ? '\u2600\uFE0F' : '\uD83C\uDF19';
        b.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
        b.title = dark ? 'Light mode' : 'Dark mode';
    }

    window.toggleTheme = function () {
        var next = current() === 'dark' ? 'light' : 'dark';
        root.setAttribute('data-theme', next);
        try { localStorage.setItem('theme', next); } catch (e) {}
        updateButton();
    };

    document.addEventListener('DOMContentLoaded', updateButton);
    if (mq.addEventListener) mq.addEventListener('change', updateButton);
})();