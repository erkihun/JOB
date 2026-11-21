{{-- Standalone and bilingual: error pages can render before the locale, session or database are available. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>{{ $status }} — {{ trans("errors.$status.title", [], 'en') }}</title>
    <style>
        /* Amharic in Abyssinica SIL, as on every other page (Ethiopic code points only). */
        @font-face { font-family: "Abyssinica SIL"; src: url("/fonts/abyssinica/AbyssinicaSIL-Regular.woff2") format("woff2"), url("/fonts/abyssinica/AbyssinicaSIL-Regular.woff") format("woff"); font-weight: 400 800; font-display: swap; unicode-range: U+1200-137F, U+1380-139F, U+2D80-2DDF, U+AB00-AB2F, U+1E7E0-1E7FF; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem; box-sizing: border-box; background: #f5f7f7; color: #111827; font-family: "Abyssinica SIL", system-ui, -apple-system, "Segoe UI", "Noto Sans Ethiopic", "Nyala", sans-serif; }
        main { width: 100%; max-width: 30rem; padding: 2.5rem 2rem; box-sizing: border-box; background: #fff; border: 1px solid #e5e7eb; border-radius: 1rem; text-align: center; }
        .status { margin: 0 0 1rem; font-size: .875rem; font-weight: 700; letter-spacing: .08em; color: #4b5563; }
        h1 { margin: 0; font-size: 1.375rem; line-height: 1.3; }
        p.message { margin: .5rem 0 0; font-size: 1rem; line-height: 1.5; color: #4b5563; }
        section + section { margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb; }
        a { display: inline-block; margin-top: 2rem; padding: .75rem 1.25rem; border-radius: .75rem; background: #111827; color: #fff; font-size: .9375rem; font-weight: 600; text-decoration: none; }
        a:focus-visible { outline: 3px solid #6b7280; outline-offset: 2px; }
    </style>
</head>
<body>
    <main>
        <p class="status">{{ $status }}</p>
        @foreach(['en', 'am'] as $errorLocale)
        <section lang="{{ $errorLocale }}">
            <h1>{{ trans("errors.$status.title", [], $errorLocale) }}</h1>
            <p class="message">{{ trans("errors.$status.message", [], $errorLocale) }}</p>
        </section>
        @endforeach
        <a href="{{ url('/') }}">{{ trans('errors.back_home', [], 'en') }} / <span lang="am">{{ trans('errors.back_home', [], 'am') }}</span></a>
    </main>
</body>
</html>
