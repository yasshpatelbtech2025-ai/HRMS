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
    <title>@yield('title') · {{ config('app.name', 'SRYMPEX') }}</title>
    <style>
        :root { --brand: #d4141f; --brand-dark: #b20f19; --ink: #111827; --muted: #6b7280; --line: #e5e7eb; --bg: #f9fafb; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 28px; padding: 24px 16px;
               background: var(--bg); color: var(--ink); font: 15px/1.5 "Funnel Display", ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif; }
        .logo { display: inline-flex; align-items: center; gap: 10px; text-decoration: none; color: var(--ink); }
        .mark { display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; background: var(--brand); color: #fff; font-weight: 900; font-size: 18px; }
        .name { font-size: 20px; font-weight: 800; letter-spacing: .12em; }
        .card { width: 100%; max-width: 460px; background: #fff; border: 1px solid var(--line); border-radius: 12px; padding: 32px 28px; text-align: center; box-shadow: 0 1px 2px rgb(16 24 40 / .05); }
        .code { margin: 0; font-size: 44px; font-weight: 800; line-height: 1; color: var(--brand); letter-spacing: .02em; }
        h1 { margin: 12px 0 8px; font-size: 22px; line-height: 1.3; }
        p { margin: 0; color: var(--muted); }
        .actions { margin-top: 24px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }
        .btn { display: inline-block; padding: 9px 16px; border-radius: 8px; font-weight: 600; text-decoration: none; border: 1px solid var(--line); color: var(--ink); background: #fff; cursor: pointer; font: inherit; font-weight: 600; }
        .btn.primary { background: var(--brand); border-color: var(--brand); color: #fff; }
        .btn:hover { border-color: #d1d5db; } .btn.primary:hover { background: var(--brand-dark); }
        .btn:focus-visible { outline: 2px solid var(--brand); outline-offset: 2px; }
        .ref { margin-top: 18px; font-size: 12px; color: var(--muted); }
    </style>
</head>
<body>
    <a href="{{ url('/') }}" class="logo" aria-label="{{ config('app.name', 'SRYMPEX') }}"><span class="mark" aria-hidden="true">S</span><span class="name">SRYMPEX</span></a>
    <main class="card">
        <p class="code">@yield('code')</p>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
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
