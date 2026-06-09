@php
    $safeThemeColor = static function (string $key, string $fallback): string {
        $value = (string) \App\Models\Setting::get($key, $fallback);

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1 ? $value : $fallback;
    };

    $themePrimary = $safeThemeColor('appearance.primary_color', '#1A56DB');
    $themeSidebar = $safeThemeColor('appearance.sidebar_color', '#1E3A8A');
    $themeAccent = $safeThemeColor('appearance.accent_color', '#FF6B2B');
@endphp
<style>
    :root {
        --color-brand: {{ $themePrimary }};
        --color-brand-dark: color-mix(in srgb, {{ $themePrimary }} 80%, black);
        --color-navy: {{ $themeSidebar }};
        --color-navy-dark: color-mix(in srgb, {{ $themeSidebar }} 80%, black);
        --color-accent: {{ $themeAccent }};
        --color-accent-dark: color-mix(in srgb, {{ $themeAccent }} 80%, black);
        --color-brand-muted: color-mix(in srgb, {{ $themePrimary }} 12%, white);
        --color-accent-muted: color-mix(in srgb, {{ $themeAccent }} 12%, white);
    }

    .admin-auth-shell {
        background:
            radial-gradient(circle at top, color-mix(in srgb, var(--color-brand) 24%, transparent), transparent 34rem),
            linear-gradient(145deg, var(--color-navy-dark), #020617 68%);
    }

    .admin-theme-primary { background-color: var(--color-brand) !important; }
    .admin-theme-primary:hover { background-color: var(--color-brand-dark) !important; }
    .admin-theme-accent { background-color: var(--color-accent) !important; }
    .admin-theme-link { color: color-mix(in srgb, var(--color-brand) 82%, white) !important; }
    .admin-theme-link:hover { color: color-mix(in srgb, var(--color-brand) 55%, white) !important; }

    .admin-theme-focus:focus {
        border-color: var(--color-brand) !important;
        --tw-ring-color: var(--color-brand) !important;
    }
</style>
