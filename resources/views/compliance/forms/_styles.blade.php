{{--
    Styles for statutory forms, matched to the old HRMS: 15px text, 22px
    bold titles, 18px semibold rule lines, bordered cells with 8px padding
    centred as on the old registers. Inside .compact (print and PDF) the
    same layout is scaled down so wide registers fit a page.
--}}
<style>
    .sheet { font-family: 'Funnel Display', 'DejaVu Sans', ui-sans-serif, system-ui, sans-serif; font-size: 15px; color: #070707; line-height: 1.4; }
    .sheet h1 { font-size: 22px; font-weight: 700; text-align: center; margin: 4px 0 6px; }
    .sheet h1.big { font-size: 35px; }
    .sheet .sub { text-align: center; font-size: 18px; font-weight: 600; margin: 0 0 6px; }
    .sheet .caption { text-align: center; font-size: 18px; font-weight: 600; margin: 6px 0 14px; }
    .sheet p { margin: 0 0 8px; }
    .sheet table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
    .sheet table.reg th, .sheet table.reg td { border: 1px solid #070707; padding: 8px; text-align: center; vertical-align: middle; }
    .sheet table.reg th { background: #f1f1f1; font-weight: 600; }
    .sheet table.reg tr.colno td { font-weight: 600; background: #fafafa; }
    .sheet table.reg td.money { white-space: nowrap; }
    .sheet table.info td { padding: 5px 6px 5px 0; vertical-align: top; text-align: left; font-size: 16px; }
    .sheet table.info td.label { width: 28%; }
    .sheet table.info td.sep { width: 2%; }
    .sheet .small { font-size: 13px; }
    .sheet .day { padding: 8px 6px !important; white-space: nowrap; }
    .sheet .empty { text-align: center; color: #555; padding: 14px; }
    .sheet input, .sheet select, .sheet textarea { font: inherit; font-size: 15px; border: 1px solid #c7c7c7; border-radius: 4px; padding: 6px 8px; width: 100%; box-sizing: border-box; background: #fff; }

    /* Form XIV: a centred employment card, as in the old system. */
    .sheet .card14 { max-width: 900px; margin: 0 auto; padding: 32px 40px; border: 1px solid #d5d5d5; }
    .sheet .card14 table.reg td { text-align: left; padding: 9px 14px; font-size: 15px; }
    .sheet .card14 table.reg td.num { text-align: center; font-weight: 600; width: 6%; }
    .sheet .card14 table.reg td.value { font-weight: 700; }
    .sheet .card14 .head td { border: none !important; padding: 8px 12px; font-size: 14px; }
    .sheet .card14 .head strong { font-size: 15px; }

    /* Form 25: larger heading, 16px rows, 14px boxed tables. */
    .sheet .form25 table.reg th, .sheet .form25 table.reg td { font-size: 14px; }

    /* Print and PDF: same layout, scaled to fit the page. */
    .sheet.compact { font-size: 13px; }
    .sheet.compact h1 { font-size: 19px; }
    .sheet.compact h1.big { font-size: 26px; }
    .sheet.compact .sub, .sheet.compact .caption { font-size: 14px; }
    .sheet.compact table.info td { font-size: 13px; padding: 3px 6px 3px 0; }
    .sheet.compact table.reg th, .sheet.compact table.reg td { padding: 5px; }
    .sheet.compact .small { font-size: 11px; }
    .sheet.compact .card14 { padding: 0; border: none; }
    .sheet.compact.mid { font-size: 11.5px; }
    /* Long registers, printed landscape: a step smaller so every column fits. */
    .sheet.compact.wide { font-size: 10px; }
    .sheet.compact.wide table.reg th, .sheet.compact.wide table.reg td { padding: 3px 2px; }
    .sheet.compact.wide .day { padding: 3px 1px !important; }
    .sheet.compact.xwide { font-size: 7.5px; }
    .sheet.compact.xwide table.reg th, .sheet.compact.xwide table.reg td { padding: 2px 1px; }
    .sheet.compact.form-a-print { font-size: 7px; }
</style>
