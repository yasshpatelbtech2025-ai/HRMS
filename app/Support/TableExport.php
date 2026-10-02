<?php

namespace App\Support;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a list page's rows as a CSV or Excel download.
 */
class TableExport
{
    public const FORMATS = ['csv', 'xlsx'];

    /**
     * @param  list<string>  $headings
     * @param  iterable<list<scalar|null>>  $rows
     */
    public static function download(string $format, string $basename, array $headings, iterable $rows): StreamedResponse
    {
        abort_unless(in_array($format, self::FORMATS, true), 404);

        if ($format === 'xlsx') {
            return response()->streamDownload(function () use ($headings, $rows) {
                $writer = new Writer;
                $writer->openToFile('php://output');
                $writer->addRow(Row::fromValues($headings));

                foreach ($rows as $row) {
                    $writer->addRow(Row::fromValues(array_map(fn ($value) => $value ?? '', $row)));
                }

                $writer->close();
            }, "{$basename}.xlsx", ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
        }

        return response()->streamDownload(function () use ($headings, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headings);

            foreach ($rows as $row) {
                fputcsv($out, $row);
            }

            fclose($out);
        }, "{$basename}.csv", ['Content-Type' => 'text/csv']);
    }
}
