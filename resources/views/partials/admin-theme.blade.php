{{--
    Site-wide theme from Settings › Appearance. Included in every layout's <head>.

    1. Brand tokens (--color-brand / navy / accent + shades).
    2. Tailwind's blue / indigo palettes are re-pointed at the primary colour and
       orange at the accent, so the many hard-coded `bg-blue-600`, `focus:ring-blue-500`,
       `text-orange-700`… classes follow the chosen theme too. Shades are derived
       with color-mix() from the variables, so the live preview in Settings updates
       them instantly.
    3. Sidebar text colours are chosen for readability on the chosen sidebar colour.
--}}
@php
    $safeThemeColor = static function (string $key, string $fallback): string {
        $value = (string) \App\Models\Setting::get($key, $fallback);

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1 ? $value : $fallback;
    };

    $themePrimary = $safeThemeColor('appearance.primary_color', '#1A56DB');
    $themeSidebar = $safeThemeColor('appearance.sidebar_color', '#1E3A8A');
    $themeAccent = $safeThemeColor('appearance.accent_color', '#FF6B2B');

    // Relative luminance (WCAG) — light sidebars get dark text.
    $luminance = static function (string $hex): float {
        $channel = static function (string $pair): float {
            $c = hexdec($pair) / 255;
            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        };
        return 0.2126 * $channel(substr($hex, 1, 2)) + 0.7152 * $channel(substr($hex, 3, 2)) + 0.0722 * $channel(substr($hex, 5, 2));
    };
    $sidebarIsLight = $luminance($themeSidebar) > 0.45;

    // Tailwind shade => mix with white (+) or black (-) around the base (600)
    $shades = [50 => 6, 100 => 12, 200 => 24, 300 => 42, 400 => 66, 500 => 86, 600 => 0, 700 => -84, 800 => -68, 900 => -54, 950 => -38];
    $palette = static function (string $name, string $var) use ($shades): string {
        $out = '';
        foreach ($shades as $shade => $mix) {
            $value = match (true) {
                $mix === 0 => "var({$var})",
                $mix > 0 => "color-mix(in srgb, var({$var}) {$mix}%, white)",
                default => 'color-mix(in srgb, var('.$var.') '.abs($mix).'%, black)',
            };
            $out .= "--color-{$name}-{$shade}: {$value};";
        }
        return $out;
    };
@endphp
<script>document.documentElement.classList.toggle('sidebar-light', {{ $sidebarIsLight ? 'true' : 'false' }});</script>
<style>
    :root {
        --color-brand: {{ $themePrimary }};
        --color-navy: {{ $themeSidebar }};
        --color-accent: {{ $themeAccent }};
        --color-brand-dark: color-mix(in srgb, var(--color-brand) 80%, black);
        --color-navy-dark: color-mix(in srgb, var(--color-navy) 80%, black);
        --color-accent-dark: color-mix(in srgb, var(--color-accent) 80%, black);
        --color-brand-muted: color-mix(in srgb, var(--color-brand) 12%, white);
        --color-accent-muted: color-mix(in srgb, var(--color-accent) 12%, white);

        {!! $palette('blue', '--color-brand') !!}
        {!! $palette('indigo', '--color-brand') !!}
        {!! $palette('orange', '--color-accent') !!}

        /* Sidebar on a dark colour: light text */
        --sb-bg:            var(--color-navy);
        --sb-text:          rgba(255,255,255,.96);
        --sb-muted:         rgba(255,255,255,.82);
        --sb-dim:           rgba(255,255,255,.66);
        --sb-border:        rgba(255,255,255,.12);
        --sb-hover:         rgba(255,255,255,.10);
        --sb-active-bg:     rgba(255,255,255,.16);
        --sb-active-text:   #ffffff;
        --sb-active-icon:   #ffffff;
        --sb-inactive-icon: rgba(255,255,255,.7);
        --sb-hover-icon:    #ffffff;
        --sb-line:          rgba(255,255,255,.14);
        --sb-badge-bg:      rgba(255,255,255,.12);
        --sb-badge-text:    rgba(255,255,255,.85);
        --sb-footer-bg:     rgba(255,255,255,.06);
        --sb-action:        rgba(255,255,255,.7);
        --sb-radial:        rgba(255,255,255,.06);
    }

    /* Sidebar on a light colour: dark text */
    :root.sidebar-light {
        --sb-text:          #0f172a;
        --sb-muted:         #334155;
        --sb-dim:           #475569;
        --sb-border:        rgba(15,23,42,.10);
        --sb-hover:         rgba(15,23,42,.06);
        --sb-active-bg:     color-mix(in srgb, var(--color-brand) 14%, white);
        --sb-active-text:   var(--color-brand-dark);
        --sb-active-icon:   var(--color-brand-dark);
        --sb-inactive-icon: #475569;
        --sb-hover-icon:    #0f172a;
        --sb-line:          rgba(15,23,42,.10);
        --sb-badge-bg:      rgba(15,23,42,.06);
        --sb-badge-text:    #334155;
        --sb-footer-bg:     rgba(15,23,42,.04);
        --sb-action:        #475569;
        --sb-radial:        transparent;
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
