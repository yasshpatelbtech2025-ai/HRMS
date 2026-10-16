<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_no }}</title>
    <style>body { margin: 0; background: #fff; }</style>
</head>
<body>
    @include('invoice._sheet', ['pdf' => true])
</body>
</html>
