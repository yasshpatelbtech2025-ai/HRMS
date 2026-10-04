<?php

namespace App\Support;

use Dompdf\Dompdf;
use Dompdf\Options;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a list page's rows as a CSV, Excel or PDF download.
 */
class TableExport
{
    public const FORMATS = ['csv', 'xlsx', 'pdf'];

    /**
     * @param  list<string>  $headings
     * @param  iterable<list<scalar|null>>  $rows
     * @param  string|null  $title  heading printed on the PDF
     */
    public static function download(string $format, string $basename, array $headings, iterable $rows, ?string $title = null): StreamedResponse
    {
        abort_unless(in_array($format, self::FORMATS, true), 404);
        Activity::record('exported', description: "{$basename}.{$format}");

        return match ($format) {
            'xlsx' => response()->streamDownload(function () use ($headings, $rows) {
                $writer = new Writer;
                $writer->openToFile('php://output');
                $writer->addRow(self::row($headings));

                foreach ($rows as $row) {
                    $writer->addRow(self::row(array_map(fn ($value) => $value ?? '', $row)));
                }

                $writer->close();
            }, "{$basename}.xlsx", ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']),

            'pdf' => response()->streamDownload(function () use ($headings, $rows, $title, $basename) {
                echo self::pdf($title ?? $basename, $headings, $rows);
            }, "{$basename}.pdf", ['Content-Type' => 'application/pdf']),

            default => response()->streamDownload(function () use ($headings, $rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, self::cells($headings));

                foreach ($rows as $row) {
                    fputcsv($out, self::cells($row));
                }

                fclose($out);
            }, "{$basename}.csv", ['Content-Type' => 'text/csv']),
        };
    }

    /**
     * CSV cells safe to open in a spreadsheet: text starting with = + - @ or
     * a tab/return would run as a formula (e.g. a name typed as
     * =HYPERLINK(...)), so it gets a leading apostrophe. Numbers, including
     * negative ones, are left alone.
     *
     * @param  array<scalar|null>  $values
     * @return array<scalar|null>
     */
    public static function cells(array $values): array
    {
        return array_map(fn ($value) => is_string($value) && ! is_numeric($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value, $values);
    }

    /**
     * An Excel row whose text is always text: OpenSpout would otherwise turn
     * a value starting with = into a live formula.
     *
     * @param  array<scalar|null>  $values
     */
    public static function row(array $values): Row
    {
        return new Row(array_map(fn ($value) => is_string($value) && str_starts_with($value, '=') ? new StringCell($value) : Cell::fromValue($value), $values));
    }

    /**
     * A landscape A4 PDF of the table.
     */
    private static function pdf(string $title, array $headings, iterable $rows): string
    {
        $e = fn ($value) => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES);
        $html = '<html><head><meta charset="utf-8"><style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; }
            h1 { font-size: 14px; margin: 0 0 4px; } p { margin: 0 0 8px; color: #555; }
            table { width: 100%; border-collapse: collapse; }
            th { background: #f3f4f6; text-align: left; }
            th, td { border: 1px solid #d1d5db; padding: 3px 4px; }
        </style></head><body>';
        $html .= '<h1>'.$e($title).'</h1><p>'.$e(now()->format('d-m-Y H:i')).'</p><table><thead><tr>';
        foreach ($headings as $heading) {
            $html .= '<th>'.$e($heading).'</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>'.implode('', array_map(fn ($cell) => '<td>'.$e($cell).'</td>', $row)).'</tr>';
        }
        $html .= '</tbody></table></body></html>';

        $dompdf = new Dompdf((new Options)->set('isRemoteEnabled', false));
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }
}
