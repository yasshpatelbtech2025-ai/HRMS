@use('App\Services\Compliance\ComplianceForms', 'F')
@if (! $employee)
    <p class="empty">{{ __('Select an employee.') }}</p>
@else
@php
    $e = $employee;
    $period = 'employee:'.$e->id;
    // Hindi and English labels; the PDF engine cannot join Devanagari letters, so the PDF is English only.
    $t = fn (string $hi, string $en) => ($pdf ?? false) ? $en : $hi.' / '.$en;
    $value = fn (string $key, string $type = 'text', int $width = 200) => view('compliance.forms.sheets._value', ['values' => $values, 'key' => $key, 'type' => $type, 'width' => $width, 'inline' => true, 'printing' => $printing])->render();
    $female = $e->gender?->value === 'female';
    $family = $e->familyMembers->keyBy('relation');
    $postal = F::address($e->address('permanent') ?? $e->address('present'));
@endphp
@unless ($printing)
<form method="POST" action="{{ route('compliance.forms.values', [$company, $form]) }}" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="period" value="{{ $period }}">
@endunless
<div class="cert">
    <table class="fields"><tr>
        <td>www.epfindia.gov.in</td>
        <td style="text-align: right;">{{ $t('मोबाइल सं', 'Mobile Number') }} : <strong>{{ $e->phone }}</strong></td>
    </tr></table>
    @unless ($pdf ?? false)<p class="sub" style="margin-bottom: 0;">कर्मचारी भविष्य निधि योजना, 1952</p>@endunless
    <h1>EMPLOYEES' PROVIDENT FUNDS SCHEME, 1952</h1>
    <p class="sub">{{ $t('प्रपत्र -19', 'Form-19') }}</p>
    <br>
    <table class="reg">
        <tr><td style="width: 6%;">1.</td><td style="text-align: left; width: 44%;">{{ $t('सदस्य का नाम', 'Name of the member') }}</td><td style="text-align: left;"><strong>{{ $e->name_as_per_uan ?: $e->full_name }}</strong></td></tr>
        <tr><td>2.</td><td style="text-align: left;">(a) {{ $t('पिता का नाम', "Father's Name") }}<br>(b) {{ $t('पति का नाम', "Husband's Name") }}</td>
            <td style="text-align: left;">(a): {{ $family['father']->full_name ?? '' }}<br>(b): {{ $female ? ($family['spouse']->full_name ?? '') : '' }}</td></tr>
        <tr><td>3.</td><td style="text-align: left;">{{ $t('जन्म तिथि', 'Date of Birth') }}</td><td style="text-align: left;">{{ $e->date_of_birth?->format('d-m-Y') }}</td></tr>
        <tr><td>4.</td><td style="text-align: left;">{{ $t('फैक्ट्री/ स्थापना का नाम व पता', 'Name and Address of the Factory / Establishment') }}</td><td style="text-align: left;">{{ $company->establishment_name }}{{ $address ? ', '.$address : '' }}</td></tr>
        <tr><td>5.</td><td style="text-align: left;">(a) {{ $t('भविष्य निधि खाता सं.', 'P.F. Account No.') }}<br>(b) {{ $t('यूनिवर्सल खाता संख्या (यू.ए.एन.)', 'Universal Account Number (UAN)') }}</td>
            <td style="text-align: left;">(a): {!! $value('pf_account_no') !!}<br>(b): {{ $e->uan_code }}</td></tr>
        <tr><td>6.</td><td style="text-align: left;">{{ $t('स्थापना में प्रवेश की तिथि', 'Date of Joining the Establishment') }}</td><td style="text-align: left;">{{ $e->date_of_joining?->format('d-m-Y') }}</td></tr>
        <tr><td>7.</td><td style="text-align: left;">{{ $t('नौकरी छोड़ने की तिथि', 'Date of leaving Service') }}</td><td style="text-align: left;">{{ $e->separation_date?->format('d-m-Y') }}</td></tr>
        <tr><td>8.</td><td style="text-align: left;">{{ $t('नौकरी छोड़ने का कारण', 'Reason of leaving Service') }}<br>
                <span class="small">Service terminated on account of (a) ill health of member (b) Contraction / Discontinuation of employer's business or (c) Other cause beyond the control of the member</span></td>
            <td style="text-align: left;">{!! $printing ? ($values->value('leaving_reason') ?: $t('व्यक्तिगत कारण', 'Personal Reasons')) : $value('leaving_reason') !!}</td></tr>
        <tr><td>9.</td><td style="text-align: left;">{{ $t('स्थायी खाता सं. ( पैन )', 'Permanent Account No. (PAN)') }}<br>
                {{ $t('क्या प्रपत्र संख्या 15 जी / 15 एच प्रस्तुत कर रहे हैं', 'Whether submitting Form No. 15G/15H, if applicable') }} (Yes / No)<br>
                <span class="small">Please enclose two copies of Form No. 15G/15H, if applicable. Only in case of service less than 5 years.</span></td>
            <td style="text-align: left;">{{ $e->pan_card_no }}<br>{!! $value('form_15g', 'text', 100) !!}</td></tr>
        <tr><td>10.</td><td style="text-align: left;">{{ $t('पत्र-व्यवहार का पूरा पता', 'Full Postal address') }}</td><td style="text-align: left;">{{ $postal }}</td></tr>
        <tr><td>11.</td><td style="text-align: left;">{{ $t('भुगतान की विधि', 'Mode of payment') }}: Put a 'Tick' against the one opted (√)<br>
                (a) By Postal Money Order at my cost OR<br>(b) By Account Payee Cheque / Electronic Mode of payment
                <span class="small">(Please attach a copy of cancelled Cheque / Attested copy of first page of Pass Book)</span></td>
            <td style="text-align: left;">
                (b) √<br>
                {{ $t('बचत बैंक खाता सं', 'Saving Bank Account No.') }}: <strong>{{ $e->account_number }}</strong><br>
                {{ $t('बैंक का नाम', 'Name of Bank') }}: <strong>{{ $e->bank_name }}</strong><br>
                {{ $t('शाखा का पता', 'Address of the Branch') }}: <strong>{{ $e->branch_address }}</strong><br>
                {{ $t('आई.एफ एस.कोड', 'IFS Code') }}: <strong>{{ $e->ifsc_code }}</strong>
            </td></tr>
    </table>
    <p>The member hereby declares that he has not been employed for two months (Yes / No): {!! $value('not_employed', 'text', 100) !!}</p>
    <p>{{ $t('प्रमाणित किया जाता है कि मेरी पूर्ण जानकारी के अनुसार उपरोक्त विवरण सही है', 'Certified that the particulars are true to the best of my knowledge.') }}</p>
    <p>{{ $t('प्रार्थी ने मेरे सामने हस्ताक्षर किए / अंगूठा लगाया है', 'The Applicant has signed / thumb impressed before me.') }}</p>
    <div class="signs">
        <div>{{ $t('सदस्य के हस्ताक्षर अथवा सदस्य का अंगूठा निशान', "Member's signature or thumb impression") }}<br><br>{{ $t('तिथि', 'Date') }} ...............................</div>
        <div>{{ $t('नियोक्ता के हस्ताक्षर', "Employer's Signature") }}<br>{{ $t('नियोक्ता का पदनाम और मोहर', 'Designation & Seal of Employer') }}</div>
    </div>
    <p style="margin-top: 24px;"><strong>{{ $t('अनुलग्नक', 'Enclosures') }}:</strong></p>

    <hr style="border: none; border-top: 1px dashed #070707; margin: 24px 0 12px;">
    <p class="sub">ADVANCE STAMPED RECEIPT (To be furnished only in case of payments through cheque)</p>
    @unless ($pdf ?? false)<p>क्षेत्रीय भविष्य निधि आयुक्त से अपने भविष्य निधि खाते के निपटान पर ₹ .................... की राशि अपने बचत बैंक खाते में प्राप्त की ।</p>@endunless
    <p>Received a sum of ₹ ........................................ from Regional Provident Fund Commissioner by deposit in my Saving Bank account towards the settlement of my Provident Fund Account.</p>
    <p class="small">Kindly do NOT paste revenue stamp in case of payments through NEFT / Electronic mode.</p>
    <div class="signs"><div></div><div style="border: 1px solid #070707; padding: 10px; text-align: center;">Affix Re 1/- Revenue stamp &amp; signature / thumb impression</div></div>

    <hr style="border: none; border-top: 1px dashed #070707; margin: 24px 0 12px;">
    <p class="sub">{{ $t('आयुक्त कार्यालय के प्रयोग के लिए', "For the use of Commissioner's Office") }}</p>
    <p>Account settled in Part / Full. Entered in F-21-A/2 and Withdrawal Register / Form 3 (F.P.F.) Form 9 (Revised)</p>
    <table class="reg">
        <tr><th>SSA</th><th>SS</th><th>P.I. No.</th><th>M.O. / Cheque</th><th>Account No.</th></tr>
        <tr style="height: 40px;"><td></td><td></td><td></td><td></td><td></td></tr>
    </table>
    <p>Passed for payment for ₹ : ..................... &nbsp; TDS Amount ₹ : ..................... &nbsp; Amount after TDS ₹ : ..................... &nbsp; TDS Rate % : .........</p>
    <p>M.O. Commission (if any) ..................... Net Amount to be paid by M.O. .....................</p>
    <div class="signs"><div></div><div>Accounts Officer<br>Date :</div></div>
    <p class="sub" style="margin-top: 16px;">( FOR USE IN CASH SECTION )</p>
    <p>Paid by cheque No. ..................... Date ..................... Vide cash book .....................</p>
</div>
@unless ($printing)
    @include('compliance.forms.sheets._save', ['period' => $period])
</form>
@endunless
@endif
