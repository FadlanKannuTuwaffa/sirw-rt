@php
    $cspNonceValue = $cspNonce ?? (app()->bound('cspNonce') ? app('cspNonce') : null);
@endphp
<script @if($cspNonceValue) nonce="{{ $cspNonceValue }}" @endif>
    (function () {
        var storageKey = 'sirw/theme-mode';
        var validModes = { light: true, dark: true, auto: true };

        var getStoredMode = function () {
            try {
                var stored = localStorage.getItem(storageKey);
                if (stored && validModes[stored]) {
                    return stored;
                }
            } catch (error) {}
            return 'auto';
        };

        var persistMode = function (mode) {
            try {
                localStorage.setItem(storageKey, mode);
            } catch (error) {}
        };

        var resolveMode = function (mode) {
            if (mode === 'light' || mode === 'dark') {
                return mode;
            }
            if (typeof window !== 'undefined' && window.matchMedia) {
                return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            return 'light';
        };

        var applyTheme = function (mode) {
            var resolved = resolveMode(mode);
            var root = document.documentElement;
            if (!root) {
                return;
            }

            root.classList.toggle('dark', resolved === 'dark');
            root.dataset.theme = resolved;
            root.dataset.themeMode = mode;

            var syncBody = function () {
                var body = document.body;
                if (!body) {
                    return;
                }
                body.classList.toggle('dark', resolved === 'dark');
                body.dataset.theme = resolved;
                body.dataset.themeMode = mode;
            };

            syncBody();
            if (!document.body) {
                document.addEventListener('DOMContentLoaded', syncBody, { once: true });
            }
        };

        var mode = getStoredMode();
        if (!validModes[mode]) {
            mode = 'auto';
            persistMode(mode);
        }

        try {
            applyTheme(mode);
        } catch (error) {
            var root = document.documentElement;
            if (!root) {
                return;
            }
            root.classList.remove('dark');
            root.dataset.theme = 'light';
            root.dataset.themeMode = 'auto';
            if (document.body) {
                document.body.classList.remove('dark');
                document.body.dataset.theme = 'light';
                document.body.dataset.themeMode = 'auto';
            }
        }
    })();
</script>
