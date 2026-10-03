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
        @page { margin: 8mm; }
    </style>
</head>
<body>
    <div class="sheet compact {{ in_array($view, ['form-a', 'form-c', 'form-18'], true) ? 'wide xwide' : (in_array($view, ['form-d', 'form-16'], true) ? 'wide' : '') }}">
        @include('compliance.forms.sheets.'.$view)
    </div>
    @if ($autoPrint)
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
