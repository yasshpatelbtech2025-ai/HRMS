<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} — {{ $company->establishment_name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Funnel+Display:wght@300..800&display=swap" rel="stylesheet">
    @include('compliance.forms._styles')
    <style>
        body { margin: 0; background: #fff; }
        @page { size: A4 {{ $landscape ? 'landscape' : 'portrait' }}; margin: 8mm; }
    </style>
</head>
<body>
    @php
        // Print size per form: landscape registers with many columns step down so every column fits the page.
        $size = match (true) {
            $view === 'form-a' => 'wide xwide form-a-print',
            in_array($view, ['form-c', 'form-18'], true) => 'wide xwide',
            in_array($view, ['form-b', 'form-d', 'form-16', 'form-17'], true) => 'wide',
            $view === 'form-20' => 'mid',
            default => '',
        };
    @endphp
    <div class="sheet compact {{ $size }}">
        @include('compliance.forms.sheets.'.$view)
    </div>
    @if ($autoPrint)
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
