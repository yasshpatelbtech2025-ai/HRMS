{{--
    The tax invoice, laid out like Srympex's "Invoice Formate" PDF: letterhead logo, one
    bordered table, letterhead footer (images cropped from that PDF, public/images/invoice).
    Plain tables and CSS only, so the same markup renders on screen and in dompdf ($pdf):
    there the footer is fixed to the bottom of the page, like the printed letterhead.
--}}
@php
    $issuer = config('invoice.issuer');
    $money = fn ($n) => \App\Support\IndianNumber::format((float) $n);
    $serviceRate = rtrim(rtrim(number_format((float) $invoice->service_charge_rate, 2), '0'), '.');
    $image = fn (string $file) => $pdf
        ? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path("images/invoice/{$file}")))
        : asset("images/invoice/{$file}");
    $no = 0;
@endphp

<style>
    .inv, .inv * { font-family: Helvetica, Arial, sans-serif; }
    .inv { color: #111; font-size: 12px; line-height: 1.35; background: #fff; }
    .inv table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .inv td, .inv th { border: 1px solid #222; padding: 3px 7px; vertical-align: top; text-align: left; }
    .inv .info td { border: 0; padding: 2px 6px; }
    .inv .sizer td { border: 0; padding: 0; height: 0; line-height: 0; font-size: 0; }
    .inv .c { text-align: center; }
    .inv .r { text-align: right; white-space: nowrap; }
    .inv .b { font-weight: bold; }
    .inv .big { font-size: 14px; }
    .inv .bigger { font-size: 15px; }
    .inv .u { text-decoration: underline; }
    .inv .logo { text-align: right; margin-bottom: 14px; }
    .inv .logo img { display: inline-block; height: 62px; }
    .inv .sign { height: 76px; }
    .inv .footer img { display: block; width: 100%; }
    @if ($pdf)
        @page { margin: 12mm 10mm 38mm; }
        .inv .footer { position: fixed; left: 0; right: 0; bottom: -30mm; }
    @else
        .inv .footer { margin-top: 18px; }
    @endif
</style>

<div class="inv">
    <div class="logo"><img src="{{ $image('logo.png') }}" alt="Srympex"></div>

    <table>
        {{-- Column widths (No | description left, right | amount); dompdf only takes them from a first row of single cells. --}}
        <tr class="sizer"><td style="width: 7%"></td><td style="width: 34%"></td><td style="width: 29%"></td><td style="width: 30%"></td></tr>
        <tr><th colspan="4" class="c">TAX INVOICE</th></tr>
        <tr>
            <td colspan="2">
                <div>Customer Name</div>
                <div class="b" style="margin-top: 3px">{{ $invoice->customer_name }}</div>
                @if ($invoice->customer_address)<div>{!! nl2br(e($invoice->customer_address)) !!}</div>@endif
                @if ($invoice->customer_gst)<div style="margin-top: 10px">GST No:- {{ $invoice->customer_gst }}</div>@endif
            </td>
            <td colspan="2" style="padding: 2px 0">
                <table class="info">
                    <tr><td class="b" style="width: 44%">Invoice No :</td><td>{{ $invoice->invoice_no }}</td></tr>
                    <tr><td class="b">Date :</td><td>{{ $invoice->invoice_date->format('d-m-Y') }}</td></tr>
                    @foreach ($issuer['details'] as $label => $value)
                        <tr><td class="b">{{ $label }}{{ $label === 'State Code' ? '' : ' :' }}</td><td>{{ $value }}</td></tr>
                    @endforeach
                </table>
            </td>
        </tr>
        <tr><th class="c">No</th><th colspan="2" class="c">Description</th><th class="c">Total Amount</th></tr>
        @foreach ($invoice->lines as $line)
            <tr><td class="c">{{ ++$no }}</td><td colspan="2">{{ $line['description'] }}</td><td class="r">{{ $money($line['amount']) }}</td></tr>
        @endforeach
        <tr><td></td><td colspan="2" class="c b">Sub Total</td><td class="r b">{{ $money($totals['sub_total']) }}</td></tr>
        <tr><td></td><td colspan="2" class="c b">{{ 'Service Charges@'.$serviceRate.'%' }}</td><td class="r b">{{ $money($totals['service_charge']) }}</td></tr>
        @foreach ($invoice->extra_lines as $line)
            <tr><td class="c">{{ ++$no }}</td><td colspan="2">{{ $line['description'] }}</td><td class="r">{{ $money($line['amount']) }}</td></tr>
        @endforeach
        <tr><td></td><td colspan="2" class="c b big">Total</td><td class="r b big">{{ $money($totals['total']) }}</td></tr>
        @foreach ($totals['taxes'] as $label => $amount)
            <tr><td></td><td colspan="2" class="c u">{{ $label }}</td><td class="r b">{{ $money($amount) }}</td></tr>
        @endforeach
        <tr><td></td><td colspan="2" class="c b bigger">Grand Total</td><td class="r b bigger">{{ $money($totals['grand_total']) }}</td></tr>
        <tr>
            <td colspan="2" class="c b sign" style="font-size: 11px">
                <div>{{ $issuer['signatory'] }}</div>
                <div style="margin-top: 52px">Authorised Signatory</div>
            </td>
            <td colspan="2" class="c b" style="font-size: 11px; vertical-align: bottom">Company Seal</td>
        </tr>
        <tr>
            <td colspan="4">
                <div>Terms &amp; Conditions :</div>
                @foreach (config('invoice.terms') as $term)<div>{{ $term }}</div>@endforeach
            </td>
        </tr>
    </table>

    <div class="footer"><img src="{{ $image('footer.png') }}" alt="{{ $issuer['name'] }}, {{ $issuer['branch_address'] }}, {{ $issuer['phone'] }}, {{ $issuer['email'] }}"></div>
</div>
