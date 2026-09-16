<?php
namespace verbb\formie\helpers;

use craft\helpers\FileHelper;

use RuntimeException;
use Throwable;
use XMLReader;
use ZipArchive;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;

class ReportExportWriter
{
    // Static Methods
    // =========================================================================

    public static function write(string $path, string $format, array $headers, iterable $rows): void
    {
        if ($format === 'xlsx') {
            self::_writeXlsx($path, $headers, $rows);
            return;
        }

        $handle = fopen($path, 'wb');

        if ($handle === false) {
            throw new RuntimeException('Unable to create export file.');
        }

        try {
            if ($format === 'csv' || $format === 'text') {
                $delimiter = $format === 'text' ? "\t" : ',';

                if ($format === 'csv') {
                    self::_writeAll($handle, "\xEF\xBB\xBF");
                }

                if ($headers) {
                    self::_writeDelimited($handle, $headers, $delimiter);
                }

                foreach ($rows as $row) {
                    self::_writeDelimited($handle, array_map(static fn($header) => $row[$header] ?? '', $headers), $delimiter);
                }
            } elseif ($format === 'json') {
                self::_writeAll($handle, '[');
                $first = true;

                foreach ($rows as $row) {
                    // Numeric column labels are still object keys, not list indexes.
                    self::_writeAll($handle, ($first ? "\n" : ",\n") . json_encode((object)$row, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
                    $first = false;
                }

                self::_writeAll($handle, $first ? ']' : "\n]");
            } elseif ($format === 'xml') {
                self::_writeAll($handle, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<report>\n");

                foreach ($rows as $row) {
                    self::_writeAll($handle, "  <submission>\n");

                    foreach ($row as $header => $value) {
                        self::_writeAll($handle, '    <column name="' . self::_xml((string)$header) . '">' . self::_xml(self::_text($value)) . "</column>\n");
                    }

                    self::_writeAll($handle, "  </submission>\n");
                }

                self::_writeAll($handle, "</report>\n");
            } else {
                throw new RuntimeException('Unsupported export format.');
            }

            if (!fflush($handle)) {
                throw new RuntimeException('Unable to flush export file.');
            }
        } finally {
            fclose($handle);
        }
    }

    private static function _writeXlsx(string $path, array $headers, iterable $rows): void
    {
        // Own the entire temporary workspace so exceptions and worker retries
        // can clean up sheet/ZIP parts as well as the final output file.
        $workspace = $path . '.parts';
        FileHelper::createDirectory($workspace);
        $options = new Options();
        $options->setTempFolder($workspace);
        $options->SHOULD_USE_INLINE_STRINGS = false;
        $writer = new Writer($options);

        try {
            $writer->openToFile($path);
            $style = (new Style())->setFontBold()->setFontColor('FFFFFF')->setBackgroundColor('000000');

            if ($headers) {
                $writer->addRow(self::_xlsxRow($headers, $style));
            }

            foreach ($rows as $row) {
                $writer->addRow(self::_xlsxRow(array_map(static fn($header) => $row[$header] ?? '', $headers)));
            }

            $writer->close();
            self::_verifyXlsx($path);
        } catch (Throwable $e) {
            try {
                $writer->close();
            } catch (Throwable) {
                // Preserve the original write/formatting failure.
            }

            throw $e;
        } finally {
            FileHelper::removeDirectory($workspace);
        }
    }

    private static function _verifyXlsx(string $path): void
    {
        // Some library writes do not report short writes. Validate the finished
        // ZIP and stream its XML before publishing a potentially truncated file.
        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::CHECKCONS) !== true) {
            throw new RuntimeException('The spreadsheet export is incomplete.');
        }

        $previousErrors = libxml_use_internal_errors(true);

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);

                if (!str_ends_with($name, '.xml') && !str_ends_with($name, '.rels')) {
                    continue;
                }

                libxml_clear_errors();
                $reader = new XMLReader();

                try {
                    if (!$reader->open('zip://' . $path . '#' . $name, 'UTF-8', LIBXML_NONET)) {
                        throw new RuntimeException('Unable to read the spreadsheet export.');
                    }

                    while ($reader->read()) {
                    }

                    if (libxml_get_errors()) {
                        throw new RuntimeException('The spreadsheet export contains incomplete XML.');
                    }
                } finally {
                    $reader->close();
                }
            }
        } finally {
            $zip->close();
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);
        }
    }

    private static function _xlsxRow(array $values, ?Style $style = null): Row
    {
        // Text cells preserve leading zeros and cannot become formulas. Values
        // already supplied as numbers or booleans retain their spreadsheet type.
        return new Row(array_map(static function($value): Cell {
            if (is_int($value) || is_float($value) || is_bool($value) || $value === null) {
                if (is_float($value) && !is_finite($value)) {
                    throw new RuntimeException('An export value is not a finite number.');
                }

                return Cell::fromValue($value);
            }

            $text = self::_text($value);
            self::_xml($text);
            return new StringCell($text, null);
        }, $values), $style);
    }

    private static function _text(mixed $value): string
    {
        return is_scalar($value) || $value === null ? (string)$value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private static function _xml(string $value): string
    {
        if (!mb_check_encoding($value, 'UTF-8') || preg_match('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', $value)) {
            throw new RuntimeException('An export value contains invalid XML characters.');
        }

        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function _writeDelimited(mixed $handle, array $values, string $delimiter): void
    {
        $values = array_map(static function($value): string {
            $text = self::_text($value);
            return preg_match('/^(?:[\x00-\x20]*[=+@-]|[\t\r\n])/', $text) ? "'" . $text : $text;
        }, $values);

        $buffer = fopen('php://temp', 'w+b');

        if ($buffer === false) {
            throw new RuntimeException('Unable to create export row buffer.');
        }

        try {
            $length = fputcsv($buffer, $values, $delimiter, '"', '');

            if ($length === false || !rewind($buffer)) {
                throw new RuntimeException('Unable to format export row.');
            }

            $bytes = stream_get_contents($buffer);

            if ($bytes === false || strlen($bytes) !== $length) {
                throw new RuntimeException('Unable to read export row.');
            }

            self::_writeAll($handle, $bytes);
        } finally {
            fclose($buffer);
        }
    }

    private static function _writeAll(mixed $handle, string $bytes): void
    {
        $offset = 0;
        $length = strlen($bytes);

        while ($offset < $length) {
            $written = fwrite($handle, substr($bytes, $offset));

            if ($written === false || $written === 0) {
                throw new RuntimeException('Unable to write export file.');
            }

            $offset += $written;
        }
    }
}
