{{-- Styles for statutory forms; plain CSS so the page, print and PDF match. --}}
<style>
    .sheet { font-family: 'DejaVu Sans', ui-sans-serif, system-ui, sans-serif; font-size: 11px; color: #111; }
    .sheet h1 { font-size: 15px; font-weight: 700; text-align: center; margin: 0 0 2px; text-transform: uppercase; }
    .sheet .sub { text-align: center; margin: 0 0 2px; font-size: 11px; }
    .sheet .caption { text-align: center; font-weight: 600; margin: 4px 0 10px; }
    .sheet table { border-collapse: collapse; width: 100%; margin-bottom: 10px; }
    .sheet table.reg th, .sheet table.reg td { border: 1px solid #555; padding: 3px 4px; vertical-align: top; }
    .sheet table.reg th { background: #f1f1f1; font-weight: 600; text-align: center; }
    .sheet table.reg td.num, .sheet table.reg th.num { text-align: center; }
    .sheet table.reg td.money { text-align: right; white-space: nowrap; }
    .sheet table.reg tr.colno td { text-align: center; font-size: 9px; color: #444; background: #fafafa; }
    .sheet table.info td { padding: 2px 4px; vertical-align: top; }
    .sheet table.info td.label { width: 28%; }
    .sheet table.info td.sep { width: 2%; }
    .sheet .small { font-size: 9px; }
    .sheet .day { font-size: 9px; padding: 2px 1px !important; text-align: center; }
    .sheet .sign { margin-top: 30px; display: flex; justify-content: space-between; }
    .sheet .empty { text-align: center; color: #666; padding: 10px; }
    .sheet input, .sheet select, .sheet textarea { font: inherit; border: 1px solid #c7c7c7; border-radius: 4px; padding: 2px 4px; width: 100%; box-sizing: border-box; }
    @media print { .sheet { font-size: 10px; } }
</style>
