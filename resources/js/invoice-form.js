/**
 * Invoice form (resources/views/invoice/form.blade.php): live totals as the
 * Excel invoice works them out. The server recalculates on save (Invoice::totals).
 *   Sub total = charge lines; service charge = sub total × rate;
 *   Total = sub total + service charge + lines added after it;
 *   GST on the total: IGST, or CGST + SGST when the customer's GST no is from our state.
 */
const num = (v) => parseFloat(v) || 0;
const round = (v) => Math.round(v * 100) / 100;
const sum = (rows) => round(rows.reduce((t, r) => t + num(r.amount), 0));
const pct = (r) => String(+num(r).toFixed(2));

export function invoiceForm(state) {
    return {
        ...state,
        fill() {
            const url = new URL(this.createUrl);
            url.searchParams.set('company', this.company);
            url.searchParams.set('month', this.month);
            window.location = url;
        },
        subTotal() { return sum(this.lines); },
        serviceCharge() { return round(this.subTotal() * num(this.serviceRate) / 100); },
        total() { return round(this.subTotal() + this.serviceCharge() + sum(this.extras)); },
        intraState() { return String(this.gst).trim().toUpperCase().startsWith(this.stateCode); },
        taxes() {
            const rate = num(this.gstRate);
            return this.intraState()
                ? [{ label: `CGST@${pct(rate / 2)}%`, amount: round(this.total() * rate / 200) }, { label: `SGST@${pct(rate / 2)}%`, amount: round(this.total() * rate / 200) }]
                : [{ label: `IGST@${pct(rate)}%`, amount: round(this.total() * rate / 100) }];
        },
        grandTotal() { return round(this.total() + this.taxes().reduce((t, x) => t + x.amount, 0)); },
        money(v) { return v.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
    };
}
