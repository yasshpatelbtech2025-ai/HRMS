<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} — {{ $company->establishment_name }}</title>
    @include('compliance.forms._styles')
    <style>
        body { margin: 0; padding: 16px; background: #fff; }
        @page { margin: 12mm; }
    </style>
</head>
<body>
    <div class="sheet">
        @include('compliance.forms.sheets.'.$view)
    </div>
    @if ($autoPrint)
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
