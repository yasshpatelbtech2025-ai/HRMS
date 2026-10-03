@use('App\Services\Compliance\ComplianceForms', 'F')
@if (! $employee)
    <p class="empty">{{ __('Select an employee.') }}</p>
@else
@php
    $e = $employee;
    $period = 'employee:'.$e->id;
    // Suggested answers from the profile (and the company's police station), shown until changed and saved.
    $values->data = array_merge(array_filter([
        'station_name' => $station->value('station_name'),
        'station_phone' => $station->value('station_phone'),
        'past_details' => $e->experiences->map(fn ($x) => trim($x->designation.' at '.$x->company_name, ' at'))->filter()->implode('; ') ?: null,
        'owner' => $contractor.($address ? ', '.$address : ''),
    ]), $values->data ?? []);
    $value = fn (string $key, int $width = 0) => view('compliance.forms.sheets._value', ['values' => $values, 'key' => $key, 'width' => $width ?: 220, 'inline' => (bool) $width, 'printing' => $printing])->render();
    $show = fn (?string $v) => $v !== null && $v !== '' ? e($v) : '-';
    $stationName = $printing ? ($values->value('station_name') ?: '__________') : $value('station_name', 200);
    $family = $e->familyMembers->map(fn ($m) => $m->full_name.' ('.ucfirst($m->relation).')')->implode(', ');
@endphp
@unless ($printing)
<form method="POST" action="{{ route('compliance.forms.values', [$company, $form]) }}" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="period" value="{{ $period }}">
@endunless
<div class="cert police">
    <table class="fields"><tr>
        <td style="width: 90px;"><img src="{{ asset('images/police-logo.png') }}" alt="" style="width: 70px;"></td>
        <td style="text-align: center;">
            <p class="sub" style="margin: 0;">{!! $stationName !!} પોલીસ સ્ટેશન</p>
            <p class="sub" style="margin: 0;">ફોન નંબર - {!! $printing ? ($values->value('station_phone') ?: '__________') : $value('station_phone', 160) !!}</p>
        </td>
        <td style="width: 90px; text-align: right;"><img src="{{ asset('images/police-logo.png') }}" alt="" style="width: 70px;"></td>
    </tr></table>
    <p class="sub" style="font-size: 16px; margin-top: 8px;">{!! $printing ? e($values->value('station_name') ?: '__________') : e($values->value('station_name') ?: '…') !!} પોલીસ સ્ટેશન વિસ્તામાં રેહતા બહાર ગામથી મજુરી અર્થે આવેલા વ્યક્તિઓનુ ઓળખ ફોર્મ</p>
    <br>
    <table class="fields"><tr>
        <td style="vertical-align: top;">
            <h6>પુરુ નામ (Full Name)</h6><p>{{ $e->full_name }}</p>
            <h6>ઉ.વ. (Age)</h6><p>{{ F::age($e) ?? '-' }}</p>
            <h6>મુળ વતનનુ સરનામું (Home Town Address)</h6><p>{!! $show(F::address($e->address('permanent') ?? $e->address('present'))) !!}</p>
        </td>
        <td style="width: 150px; text-align: right; vertical-align: top;">
            @if ($e->photo_path && ! ($pdf ?? false))
                <img src="{{ route('employees.file', [$e, 'photo']) }}" alt="પાસ્પોર્ટ સાઈઝ ફોટો" style="width: 138px; height: 177px; object-fit: cover; border: 1px solid #070707;">
            @else
                <div class="photo" style="float: none; margin: 0 0 0 auto;">પાસ્પોર્ટ સાઈઝ ફોટો</div>
            @endif
        </td>
    </tr></table>
    <h6>મોબાઈલ નંબર (MOBILE NO.)</h6><p>{!! $show($e->phone) !!}</p>
    <h6>અધાર કાર્ડ / ચુટણી કાર્ડ / લાઈસ્ન્સની વિગત (ADHARCARD/ELECTION CARD/LICENSE DETAILS)</h6>
    <p>ADHARCARD - {{ $e->aadhar_card_no }} | LICENSE DETAILS - {{ $e->driving_license_no }}</p>
    <h6>વાહન જો હોય તોતેને રજીસ્ટ્રેશન નંબર (VEHICLE REG. NO.)</h6><p>{!! $printing ? $show($values->value('vehicle_no')) : $value('vehicle_no') !!}</p>
    <h6>કુટુંબના સભ્યની વિગત, નામ, સરનામુ, મોબાઈલ નંબર (FAMILY MEMBER DETAILS AND NAME, MO. NO.)</h6>
    <p>{{ $family }}@if ($family && ($printing ? $values->value('family_contact') : true)) — @endif{!! $printing ? e($values->value('family_contact') ?? '') : $value('family_contact', 300) !!}</p>
    <h6>વતનના પોલીસ સ્ટેશનની વિગત (HOME TOWN POLICE STATION DETAIL)</h6><p>{!! $printing ? $show($values->value('hometown_police')) : $value('hometown_police') !!}</p>
    <h6>વતનના સરપંચ/મુખી/પ્રધાનનું નામ, સરનામું તથા મોબાઈન નંબરની વિગત (NAME, ADDRESS AND MOBILE DETAILS OF SARPANCH / CHIEF PRADHAN OF THE VILLAGE)</h6>
    <p>{!! $printing ? $show($values->value('sarpanch')) : $value('sarpanch') !!}</p>
    <h6>મુકાદમ નું નામ, સરનામું તથા મોબાઈલ નંબરની વિગત (SITE SUPERVISOR NAME, ADDRESS AND MO. NO.)</h6>
    <p>{!! $printing ? $show($values->value('supervisor')) : $value('supervisor') !!}</p>
    <h6>ભુતકાળની વિગત/પહેલા કયા કામ કરતા હતા/કોની વાડીએ હતા /કઇ કંપનીમાં હતા શું કામ કરતા હતા વિગેરે માહીતી (PAST DETAILS / WHERE DO YOU WORK BEFORE / TYPE OF WORK YOU DO / IN WHICH COMPANY YOU ARE)</h6>
    <p>{!! $printing ? $show($values->value('past_details')) : $value('past_details') !!}</p>
    <h6>હાલના મકાન /વાડી/હોટેલ/ફેક્ટરી ના મલીકનું નામ, સરનામુ તથા મોબાઈલ નંબરની વિગત (NAME, ADDRESS AND MO. NO. OF THE OWNER OF THE CURRENT FACTORY)</h6>
    <p>{!! $printing ? $show($values->value('owner')) : $value('owner') !!}</p>
    <h6>અન્ય વિગત</h6><p>{!! $printing ? $show($values->value('other')) : $value('other') !!}</p>

    <table class="fields" style="margin-top: 12px;">
        <tr><td><strong>નકલ</strong></td><td style="text-align: right;">તારીખ (DATE) {!! $printing ? e($values->value('copy_date') ? \Carbon\Carbon::parse($values->value('copy_date'))->format('d-m-Y') : '') : view('compliance.forms.sheets._value', ['values' => $values, 'key' => 'copy_date', 'type' => 'date', 'width' => 170, 'inline' => true, 'printing' => false])->render() !!}</td></tr>
        <tr><td>(૧) આધાર કાર્ડ / ચુટણી કાર્ડ / લાઈસ્ન્સની ઝેરોક્ષ</td><td style="text-align: right;">સીરીયલ નં. {!! $printing ? e($values->value('serial_no') ?? '') : $value('serial_no', 120) !!}</td></tr>
        <tr><td>(૨) પાસ પાર્ટ સાઈઝ ફોટો ૦૧</td><td></td></tr>
    </table>
    <table class="reg" style="margin-top: 16px;">
        <tr><td style="text-align: left; width: 40%;">AGENCY / CONTRACTOR NAME</td><td style="text-align: left;">{!! $printing ? e($values->value('contractor_name') ?? '') : $value('contractor_name') !!}</td></tr>
        <tr><td style="text-align: left;">CONTRACTOR / OWNER NAME</td><td style="text-align: left;">{!! $printing ? e($values->value('owner_name') ?? '') : $value('owner_name') !!}</td></tr>
        <tr><td style="text-align: left;">MO. NO.</td><td style="text-align: left;">{!! $printing ? e($values->value('contractor_phone') ?? '') : $value('contractor_phone') !!}</td></tr>
        <tr><td style="text-align: left;">WORK IN COMPANY</td><td style="text-align: left;">{{ $company->establishment_name }}</td></tr>
        <tr><td style="text-align: left;">SITE SUPERVISOR MO. NO.</td><td style="text-align: left;">{!! $printing ? e($values->value('supervisor_phone') ?? '') : $value('supervisor_phone') !!}</td></tr>
    </table>
</div>
@unless ($printing)
    <p class="small" style="color: #555;">The police station name and phone are kept for the whole company once saved. Past details and the factory owner are suggested from the profile; change them if needed and Save.</p>
    @include('compliance.forms.sheets._save', ['period' => $period])
</form>
@endunless
@endif
