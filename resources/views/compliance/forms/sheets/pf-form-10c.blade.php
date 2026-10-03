@use('App\Services\Compliance\ComplianceForms', 'F')
@if (! $employee)
    <p class="empty">{{ __('Select an employee.') }}</p>
@else
@php
    $e = $employee;
    $period = 'employee:'.$e->id;
    $value = fn (string $key, string $type = 'text', int $width = 200) => view('compliance.forms.sheets._value', ['values' => $values, 'key' => $key, 'type' => $type, 'width' => $width, 'inline' => true, 'printing' => $printing])->render();
    $female = $e->gender?->value === 'female';
    $family = $e->familyMembers->keyBy('relation');
    $father = $family['father']->full_name ?? '';
    $postal = $e->address('permanent') ?? $e->address('present');
    $dots = '....................................';
@endphp
@unless ($printing)
<form method="POST" action="{{ route('compliance.forms.values', [$company, $form]) }}" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="period" value="{{ $period }}">
@endunless
<div class="cert">
    <table class="fields"><tr>
        <td>Mobile Number : <strong>{{ $e->phone }}</strong></td>
        <td style="text-align: right;">For Office Use Only<br>Claim I.D. {{ $dots }}</td>
    </tr></table>
    <h1 style="font-size: 18px;">FORM 10C FOR CLAIMING WITHDRAWAL BENEFIT / SCHEME CERTIFICATE</h1>
    <p class="sub">EMPLOYEES' PENSION SCHEME, 1995</p>
    <p style="text-align: center;">Read the instructions before filling up this form</p>
    <p style="text-align: center; font-weight: 700;">WITHDRAWAL BENEFIT IS NOT ADMISSIBLE IF MEMBERSHIP IS LESS THAN 180 DAYS EXCLUDING NON CONTRIBUTING PERIOD</p>
    <table class="fields">
        <tr><td class="no">1.</td><td class="label">Name of the Member (In Block Letters)<br>Name of the claimant(s)</td><td class="sep">:</td><td><strong>{{ mb_strtoupper($e->name_as_per_uan ?: $e->full_name) }}</strong><br>{!! $value('claimant') !!}</td></tr>
        <tr><td class="no">2.</td><td class="label">Date of Birth (dd/mm/yyyy)</td><td class="sep">:</td><td>{{ $e->date_of_birth?->format('d/m/Y') }}</td></tr>
        <tr><td class="no">3.</td><td class="label">Father's Name<br>Husband's Name (if applicable)</td><td class="sep">:</td><td>{{ $father }}<br>{{ $female ? ($family['spouse']->full_name ?? '') : '' }}</td></tr>
        <tr><td class="no">4.</td><td class="label">Name &amp; Address of the Establishment in which the member was last employed</td><td class="sep">:</td><td>{{ $company->establishment_name }}{{ $address ? ', '.$address : '' }}</td></tr>
        <tr><td class="no">5.</td><td class="label">Code No. &amp; Account No.</td><td class="sep">:</td><td>
            Region / Off Code {!! $value('region_code', 'text', 90) !!} &nbsp; Estt. Code No. {!! $value('estt_code', 'text', 110) !!} &nbsp; A/c No. {!! $value('account_no', 'text', 110) !!}
        </td></tr>
        <tr><td class="no">5A.</td><td class="label">Date of Joining the Estt.</td><td class="sep">:</td><td>{{ $e->date_of_joining?->format('d/m/Y') }}</td></tr>
        <tr><td class="no">6.</td><td class="label">Reason for leaving service &amp; Date of Leaving</td><td class="sep">:</td><td>{!! $value('leaving_reason') !!} &nbsp; {{ $e->separation_date?->format('d/m/Y') }}</td></tr>
        <tr><td class="no">7.</td><td class="label">Full Address (In Block Letters)</td><td class="sep">:</td><td>
            Sh. / Smt. / Km. <strong>{{ mb_strtoupper($e->full_name) }}</strong><br>
            S/o, W/o, D/o. <strong>{{ mb_strtoupper($female ? (($family['spouse']->full_name ?? '') ?: $father) : $father) }}</strong><br>
            Address : {{ mb_strtoupper(collect([$postal?->address_line_1, $postal?->address_line_2, $postal?->village, $postal?->taluka, $postal?->district, $postal?->state])->filter()->implode(', ')) }}<br>
            PIN : {!! $value('pin', 'text', 100) !!}
        </td></tr>
    </table>
    <div class="signs"><div>Signature or Left / Right hand thumb impression of the member</div><div>Employer's Signature</div></div>
    <table class="fields" style="margin-top: 16px;">
        <tr><td class="no">8.</td><td colspan="3">Are you willing to accept Scheme Certificate in lieu of withdrawal benefits : Yes / No {!! $value('scheme_certificate', 'text', 80) !!}<br>
            <span class="small">Withdrawal benefit is not admissible if the membership is less than 180 days excluding non contributory period of service.</span></td></tr>
        <tr><td class="no">9.</td><td colspan="3">Particulars of Family (Spouse &amp; Children &amp; Nominee) (applicable only for Scheme Certificate option)</td></tr>
    </table>
    <table class="reg">
        <tr><th>Name</th><th>Date of Birth</th><th>Relationship with Member</th><th>Name of the guardian of minor</th></tr>
        @forelse ($e->familyMembers->whereIn('relation', ['spouse', 'son', 'daughter', 'child'])->merge($e->familyMembers->where('is_nominee', true))->unique('id') as $member)
            <tr><td>{{ $member->full_name }}</td><td class="nw">{{ $member->date_of_birth?->format('d/m/Y') }}</td><td>{{ ucfirst($member->relation) }}</td><td></td></tr>
        @empty
            <tr style="height: 36px;"><td></td><td></td><td></td><td></td></tr>
        @endforelse
    </table>
    <table class="fields">
        <tr><td class="no">10.</td><td colspan="3">In case of death of member after attaining the age of 58 years without filing the claim:-<br>
            Date of death of the member {!! $value('death_date', 'date', 170) !!}<br>Name of the Claimant(s) and relationship with the member {!! $value('death_claimant') !!}</td></tr>
        <tr><td class="no">11.</td><td colspan="3">Mode of remittance (put a tick in the box against the one opted)<br>
            (a) By postal money order at my cost to the address given against item No. 7<br>
            (b) By account payee cheque / electronic mode sent directly for credit to my S.B. A/C (Scheduled Bank / P.O.) under intimation to me. √<br>
            S.B. Account No. : <strong>{{ $e->account_number }}</strong><br>
            Name of the Bank (In Block Letters) : <strong>{{ mb_strtoupper($e->bank_name ?? '') }}</strong><br>
            Branch (In Block Letters) : <strong>{{ mb_strtoupper($e->branch_address ?? '') }}</strong><br>
            IFS Code : <strong>{{ $e->ifsc_code }}</strong></td></tr>
        <tr><td class="no">12.</td><td colspan="3">Are you availing pension under EPS-95? Yes / No {!! $value('eps_pension', 'text', 80) !!}<br>
            If yes, indicate PPO No {!! $value('ppo_no', 'text', 140) !!} By whom issued {!! $value('ppo_issuer') !!}</td></tr>
    </table>
    <p>Certified that the particulars are true to the best of my knowledge</p>
    <div class="signs"><div>Date ...............................</div><div>Signature or left hand thumb impression of the Member / Claimant</div></div>
    <p style="text-align: right; margin-top: 24px;">Employer's Signature</p>

    <hr style="border: none; border-top: 1px dashed #070707; margin: 24px 0 12px;">
    <p class="sub">Advance Stamped Receipt<br><span style="font-weight: 400; font-size: 15px;">[To be furnished only in case of (b) above]</span></p>
    <p>Received a sum of ₹ .................... (Rupees ..........................................) only from Regional Provident Fund Commissioner / Officer-in-charge of Sub-Regional Office .................... by deposit in my savings Bank A/c towards the settlement of my Pension Fund Account.</p>
    <p class="small">(The space should be left blank which shall be filled by Regional Provident Fund Commissioner / Officer-in-charge)</p>
    <div class="signs"><div></div><div style="border: 1px solid #070707; padding: 10px; text-align: center;">Rs 1/- Revenue stamp</div></div>
    <p style="margin-top: 16px;">Certified that the particulars of the member given are correct and the member has signed / thumb impressed before me.</p>
    <p>The details of wages and period of non-contributory service of the member are as under:<br>
        <span class="small">(Form 3A/7 (EPS) enclosed for the period for which it was not sent to Employees' Provident Fund Office)</span></p>
    <p>Wages (Basic + D.A.) as on 15.11.95 (if applicable) ₹ {!! $value('wages_1995', 'number', 140) !!}<br>Wages as on the date of exit ₹ {!! $value('wages_exit', 'number', 140) !!}</p>
    <p>Period of non contributory Service : Year/Month .................... No. of days ....................</p>
    <div class="signs"><div>Date ..........................</div><div>Signature of Employer / Authorised Official</div></div>

    <hr style="border: none; border-top: 1px dashed #070707; margin: 24px 0 12px;">
    <p class="sub">(For the use of commissioner's office)</p>
    <p>Under ₹ .................... P.I. No. .................... M.O. / Cheque.<br>Passed for payment for ₹ .................... (in words) ........................................<br>
        M.O. Commission (if any) .................... net amount to be paid by M.O. .................... towards withdrawal benefit.</p>
    <p style="text-align: right;">SSA &nbsp; S.S. &nbsp; A.AO.</p>
    <p class="sub">(For use in Cash Section)</p>
    <p>Paid by inclusion in cheque No. .................... Dt .................... vide Cash Book (Bank) Account No. 10 Debit item No. ....................</p>
    <p style="text-align: right;">S.S. &nbsp; AC (Cash)</p>
    <p>For issue of S.C., IDS is enclosed &mdash; SSA &nbsp; S.S. &nbsp; A.AO. &nbsp; APFC (A/cs.)</p>
    <p class="sub">(For use in Pension Section)</p>
    <p>Scheme Certificate bearing the control No. .................... issued on .................... and entered in the Scheme Certificate Control Register.</p>
    <p style="text-align: right;">SSA &nbsp; S.S. &nbsp; A.AO. &nbsp; APFC (A/cs.)</p>
</div>
@unless ($printing)
    @include('compliance.forms.sheets._save', ['period' => $period])
</form>
@endunless
@endif
