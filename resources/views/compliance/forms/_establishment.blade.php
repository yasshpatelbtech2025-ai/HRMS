{{-- Contractor / establishment block shared by the Contract Labour registers. Optional: $monthLine, $wagePeriod. --}}
<table class="info">
    <tr>
        <td class="label">{{ __('Name and address of contractor') }}</td><td class="sep">:-</td><td>{{ $contractor }}</td>
        <td class="label">{{ __('Name and address of principal employer') }}</td><td class="sep">:-</td><td>{{ $company->parent ? $company->establishment_name.($address ? ', '.$address : '') : '' }}</td>
    </tr>
    <tr>
        <td class="label">{{ __('Name and address of establishment in/under which contract is carried on') }}</td><td class="sep">:-</td><td>{{ $company->establishment_name }}{{ $address ? ', '.$address : '' }}</td>
        <td class="label">{{ __('Nature and location of work') }}</td><td class="sep">:-</td><td>{{ $company->district }}</td>
    </tr>
    @if (! empty($monthLine))
        <tr>
            <td class="label">{{ ! empty($wagePeriod) ? __('Wage Period') : '' }}</td><td class="sep">{{ ! empty($wagePeriod) ? ':-' : '' }}</td><td>{{ $wagePeriod ?? '' }}</td>
            <td class="label">{{ __('For the month of') }}</td><td class="sep">:-</td><td>{{ $monthLine }}</td>
        </tr>
    @endif
</table>
