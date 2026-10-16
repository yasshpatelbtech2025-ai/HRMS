<?php

namespace App\Support;

use DateTimeInterface;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reading the first sheet of an uploaded .xlsx/.csv into rows keyed by
 * heading, and a blank template to fill in. Used by the company and
 * attendance bulk uploads.
 */
class SheetImport
{
    /**
     * @param  list<string>  $headings  the expected headings, in template order
     * @return array<int, array<string, string>> heading => cell text, by sheet row number
     */
    public static function read(string $path, string $extension, array $headings): array
    {
        $reader = $extension === 'csv' ? new CsvReader : new XlsxReader;
        $reader->open($path);
        $rows = [];
        $found = null;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $number => $row) {
                $values = array_map(fn ($cell) => $cell instanceof DateTimeInterface ? $cell->format('Y-m-d H:i') : trim((string) $cell), $row->toArray());

                if ($found === null) {
                    $found = array_map(fn ($value) => mb_strtolower($value), $values);

                    continue;
                }

                if (collect($values)->filter(fn ($value) => $value !== '')->isEmpty()) {
                    continue;
                }

                foreach ($headings as $heading) {
                    $column = array_search(mb_strtolower($heading), $found, true);
                    $rows[$number][$heading] = $column === false ? '' : ($values[$column] ?? '');
                }
            }

            break;
        }

        $reader->close();

        return $rows;
    }

    /**
     * @param  list<string>  $headings
     * @param  list<list<string>>  $examples
     */
    public static function template(string $filename, array $headings, array $examples = []): StreamedResponse
    {
        return response()->streamDownload(function () use ($headings, $examples) {
            $writer = new Writer;
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues($headings));

            foreach ($examples as $example) {
                $writer->addRow(TableExport::row($example));
            }

            $writer->close();
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
