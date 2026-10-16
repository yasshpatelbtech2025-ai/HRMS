<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\Trashable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A tax invoice to a client company. Lines in `lines` carry the service
 * charge; `extra_lines` (OT, food…) are added after it. GST goes on the lot.
 */
#[Fillable(['company_id', 'invoice_no', 'invoice_date', 'month', 'customer_name', 'customer_address', 'customer_gst',
    'lines', 'extra_lines', 'service_charge_rate', 'gst_rate', 'grand_total', 'created_by'])]
class Invoice extends Model
{
    use Auditable, BelongsToCompany, HasUuids, Trashable;

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date', 'month' => 'date', 'lines' => 'array', 'extra_lines' => 'array',
            'service_charge_rate' => 'decimal:2', 'gst_rate' => 'decimal:2', 'grand_total' => 'decimal:2',
        ];
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class)->withTrashed();
    }

    public function auditLabel(): ?string
    {
        return __('Invoice :no', ['no' => $this->invoice_no]);
    }

    /** Same state as Srympex (GST number starts with its state code): CGST + SGST instead of IGST. */
    public function isIntraState(): bool
    {
        return str_starts_with(strtoupper(trim((string) $this->customer_gst)), config('invoice.issuer.state_code'));
    }

    /**
     * Sub total, service charge, extras, total, the tax lines and the grand
     * total, in paise-exact amounts (not rounded to rupees).
     *
     * @return array{sub_total: float, service_charge: float, total: float, taxes: array<string, float>, grand_total: float}
     */
    public function totals(): array
    {
        $sum = fn (?array $lines) => collect($lines)->sum(fn ($line) => (float) ($line['amount'] ?? 0));
        $subTotal = round($sum($this->lines), 2);
        $serviceCharge = round($subTotal * (float) $this->service_charge_rate / 100, 2);
        $total = round($subTotal + $serviceCharge + $sum($this->extra_lines), 2);
        $rate = (float) $this->gst_rate;
        $label = fn (string $tax, float $r) => $tax.'@'.rtrim(rtrim(number_format($r, 2), '0'), '.').'%';

        $taxes = $this->isIntraState()
            ? [$label('CGST', $rate / 2) => round($total * $rate / 200, 2), $label('SGST', $rate / 2) => round($total * $rate / 200, 2)]
            : [$label('IGST', $rate) => round($total * $rate / 100, 2)];

        return [
            'sub_total' => $subTotal,
            'service_charge' => $serviceCharge,
            'total' => $total,
            'taxes' => $taxes,
            'grand_total' => round($total + array_sum($taxes), 2),
        ];
    }
}
