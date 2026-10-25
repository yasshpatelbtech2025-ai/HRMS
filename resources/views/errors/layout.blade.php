{{--
    Shared error page. Self-contained (inline styles, no database, assets or
    sign-in), so it still renders when the error is the database or the build.
    Sections: code, title, message; optional `action` (defaults to Dashboard).
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') · {{ config('branding.name') }}</title>
    <script>try { var t = localStorage.getItem('theme'); if (t === 'dark' || (t !== 'light' && matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark'); } catch (e) {}</script>
    <style>
        /* The Register tokens (resources/css/app.css), inlined so this page needs no build. */
        :root { --primary: #2445A8; --primary-hover: #1C378C; --on-primary: #fff; --ink: #131722; --ink-2: #566074; --ink-3: #636C7E; --line: #DFE3EA; --line-strong: #C4CBD6; --bg: #F5F7FA; --surface: #fff; --sunken: #EFF2F6; color-scheme: light; }
        :root.dark { --primary: #8FA8F5; --primary-hover: #A9BCF8; --on-primary: #0E1117; --ink: #E7EAF0; --ink-2: #AAB2C0; --ink-3: #8D96A6; --line: #2A303C; --line-strong: #3C4453; --bg: #0E1117; --surface: #161A22; --sunken: #1D222C; color-scheme: dark; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 32px; padding: 24px 16px;
               background: var(--bg); color: var(--ink); font: 14px/1.5 "Instrument Sans", ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif; }
        .logo { display: inline-flex; align-items: center; text-decoration: none; border-radius: 6px; }
        .logo img { height: 32px; width: auto; display: block; }
        .mark { display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 7px; background: #DD2A1B; color: #fff; font-weight: 600; font-size: 15px; }
        .card { width: 100%; max-width: 480px; background: var(--surface); border: 1px solid var(--line); border-radius: 12px; padding: 32px 28px 28px; }
        .head { position: relative; padding-inline-start: 16px; padding-bottom: 18px; border-bottom: 1px solid var(--line); }
        .head::before { content: ''; position: absolute; inset-inline-start: 0; top: 2px; bottom: 20px; width: 2px; border-radius: 1px; background: var(--primary); }
        .code { margin: 0; font: 500 13px/1 "IBM Plex Mono", ui-monospace, monospace; color: var(--ink-3); letter-spacing: .02em; }
        h1 { margin: 10px 0 0; font-size: 24px; line-height: 1.25; font-weight: 600; letter-spacing: -.015em; }
        .msg { margin: 18px 0 0; color: var(--ink-2); }
        .actions { margin-top: 24px; display: flex; flex-wrap: wrap; gap: 8px; }
        .btn { display: inline-flex; align-items: center; height: 38px; padding: 0 16px; border-radius: 6px; text-decoration: none; border: 1px solid var(--line-strong); color: var(--ink); background: var(--surface); cursor: pointer; font: inherit; font-weight: 500; transition: background-color 150ms, border-color 150ms; }
        .btn.primary { background: var(--primary); border-color: var(--primary); color: var(--on-primary); }
        .btn:hover { background: var(--sunken); } .btn.primary:hover { background: var(--primary-hover); }
        .btn:focus-visible, .logo:focus-visible { outline: 2px solid var(--primary); outline-offset: 2px; }
        .ref { margin: 20px 0 0; padding-top: 14px; border-top: 1px solid var(--line); font-size: 12px; color: var(--ink-3); }
        @media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
    </style>
</head>
<body>
    @php $logo = config('branding.logo'); $logo = $logo && is_file(public_path($logo)) ? asset($logo) : null; @endphp
    <a href="{{ url('/') }}" class="logo" aria-label="{{ config('branding.name') }}">
        @if ($logo)<img src="{{ $logo }}" alt="">@else<span class="mark" aria-hidden="true">{{ config('branding.mark') }}</span>@endif
    </a>
    <main class="card">
        <div class="head">
            <p class="code">{{ __('Error') }} @yield('code')</p>
            <h1>@yield('title')</h1>
        </div>
        <p class="msg">@yield('message')</p>
        <div class="actions">
            @section('action')
                <a href="{{ url('/dashboard') }}" class="btn primary">{{ __('Go to dashboard') }}</a>
                <button type="button" class="btn" onclick="history.length > 1 ? history.back() : location.assign('{{ url('/') }}')">{{ __('Go back') }}</button>
            @show
        </div>
        @hasSection('reference')<p class="ref">@yield('reference')</p>@endif
    </main>
</body>
</html>
