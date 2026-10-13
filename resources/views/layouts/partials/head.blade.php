<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#F7F7F4">
<title>{{ isset($title) && $title ? $title.' · ' : '' }}{{ config('branding.name') }}</title>
{{-- Apply saved theme and sidebar width before first paint (no flash). --}}
<script>
    try {
        const s = localStorage, d = document.documentElement;
        const theme = JSON.parse(s.getItem('_x_theme') || '"light"');
        if (theme === 'dark' || (theme === 'system' && matchMedia('(prefers-color-scheme: dark)').matches)) d.dataset.theme = 'dark';
        if (s.getItem('_x_sidebar:rail') === 'true') d.classList.add('rail');
    } catch (e) {}
</script>
@vite(['resources/css/app.css', 'resources/js/app.js'])
