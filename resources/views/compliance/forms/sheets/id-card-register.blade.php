@use('App\Services\Compliance\ComplianceForms', 'F')
<h1>IDENTITY CARD REGISTER</h1>
<br>
<p style="font-size: 16px; margin-bottom: 20px;"><strong>NAME &amp; ADDRESS OF FACTORY :</strong> {{ $company->establishment_name }}{{ $address ? ' | '.$address : '' }}</p>
<table class="reg">
    <tr>
        <th>Sr no</th><th>Full name and address of the workers</th><th>Date of birth the worker</th><th>Date of joining the service of the factory</th>
        <th>Recent passport size photograph of the worker</th><th>Signature or thumb impression of the worker</th>
        <th>Signature of manager or authorised agent</th><th>Date Of Issue</th><th>Remarks</th>
    </tr>
    @forelse ($employees as $e)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td style="text-align: left;">{{ $e->full_name }}<br><span class="small">{{ F::residence($e) }}</span></td>
            <td class="nw">{{ $e->date_of_birth?->format('d-m-Y') }}</td>
            <td class="nw">{{ $e->date_of_joining?->format('d-m-Y') }}</td>
            <td style="height: 70px; min-width: 80px;"></td>
            <td style="min-width: 90px;"></td>
            <td style="min-width: 90px;"></td>
            <td class="nw">{{ $e->id_card_assigned_on?->format('d-m-Y') }}</td>
            <td></td>
        </tr>
    @empty
        <tr><td colspan="9" class="empty">No current employees</td></tr>
    @endforelse
</table>
