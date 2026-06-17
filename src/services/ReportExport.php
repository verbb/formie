<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\helpers\Variables;
use verbb\formie\models\Report;

use Craft;
use craft\base\Component;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\FileHelper;
use craft\web\BaseSpreadsheetResponseFormatter;
use craft\web\CsvResponseFormatter;
use craft\web\Response as CraftResponse;

use DateTime;

class ReportExport extends Component
{
    // Constants
    // =========================================================================

    public const DEFAULT_CHUNK_SIZE = 100;


    // Public Methods
    // =========================================================================

    public function exportCsv(
        Report $report,
        ?ElementQueryInterface $query = null,
        int $chunkSize = self::DEFAULT_CHUNK_SIZE,
        ?array $columnOverride = null,
    ): string {
        return $this->export($report, 'csv', $query, $chunkSize, $columnOverride)['path'];
    }

    public function export(
        Report $report,
        string $format,
        ?ElementQueryInterface $query = null,
        int $chunkSize = self::DEFAULT_CHUNK_SIZE,
        ?array $columnOverride = null,
    ): array {
        $format = strtolower(trim($format));
        $allowed = ['csv', 'json', 'xml', 'text', 'xlsx'];

        if (!in_array($format, $allowed, true)) {
            $format = 'csv';
        }

        $query ??= Formie::$plugin->getReportQuery()->buildSubmissionQuery($report);
        $query->limit(null)->offset(null);
        $headers = $this->_resolveExportHeaders($report, $columnOverride);
        $rows = $this->_collectRows($report, $query, $chunkSize, $columnOverride);
        $date = new DateTime();
        $basename = $this->resolveBasename($report, $date);
        $extension = $this->_resolveExtension($format);
        $downloadFilename = $basename . '.' . $extension;
        $tempPath = Craft::$app->getPath()->getTempPath()
            . DIRECTORY_SEPARATOR
            . $basename
            . '-'
            . uniqid('', true)
            . '.'
            . $extension;

        $result = match ($format) {
            'json' => $this->_writeJsonExport($tempPath, $rows),
            'xml' => $this->_writeXmlExport($tempPath, $rows),
            'text' => $this->_writeSpreadsheetExport($tempPath, $rows, $headers, 'text'),
            'xlsx' => $this->_writeSpreadsheetExport($tempPath, $rows, $headers, 'xlsx'),
            default => $this->_writeSpreadsheetExport($tempPath, $rows, $headers, 'csv'),
        };

        $result['filename'] = $downloadFilename;

        return $result;
    }

    public function resolveBasename(Report $report, ?DateTime $date = null): string
    {
        $date ??= new DateTime();
        $settings = $report->getSettingsModel();
        $template = trim((string)($settings->export['filename'] ?? ''));

        if ($template === '') {
            $template = 'formie-report-{handle}-{timestamp}';
        }

        $replacements = [
            '{handle}' => (string)$report->handle,
            '{name}' => $this->_sanitizeFilenameSegment((string)$report->name),
            // Legacy report date tokens.
            '{date}' => $date->format('Y-m-d'),
            '{time}' => $date->format('H-i'),
            '{datetime}' => $date->format('Y-m-d-H-i'),
        ];

        $template = strtr($template, $replacements);
        $variables = Variables::getContextVariables($date);

        $template = (string)preg_replace_callback('/\{[^{}]+\}/', function(array $matches) use ($variables): string {
            return Variables::resolveContextReference((string)($matches[0] ?? ''), $variables);
        }, $template);

        return $this->_sanitizeFilename($template);
    }

    public function resolveFilename(Report $report, string $format, ?DateTime $date = null): string
    {
        $format = strtolower(trim($format));
        $allowed = ['csv', 'json', 'xml', 'text', 'xlsx'];

        if (!in_array($format, $allowed, true)) {
            $format = 'csv';
        }

        return $this->resolveBasename($report, $date) . '.' . $this->_resolveExtension($format);
    }

    public function getFilename(Report $report, ?DateTime $date = null): string
    {
        return $this->resolveFilename($report, 'csv', $date);
    }

    private function _collectRows(
        Report $report,
        ElementQueryInterface $query,
        int $chunkSize,
        ?array $columnOverride,
    ): array {
        $columns = Formie::$plugin->getReportColumns()->resolveColumns($report, $columnOverride);
        $display = $report->getSettingsModel()->display;
        $rows = [];
        $offset = 0;

        while (true) {
            $chunkQuery = clone $query;
            $submissions = $chunkQuery->limit($chunkSize)->offset($offset)->all();

            if (!$submissions) {
                break;
            }

            foreach ($submissions as $submission) {
                $rows[] = Formie::$plugin->getReportColumns()->formatRowAssoc($submission, $columns, $display);
            }

            $offset += $chunkSize;

            if (count($submissions) < $chunkSize) {
                break;
            }
        }

        return $this->_normaliseRows($rows);
    }

    private function _resolveExportHeaders(Report $report, ?array $columnOverride): array
    {
        $columns = Formie::$plugin->getReportColumns()->resolveColumns($report, $columnOverride);

        return array_column($columns, 'header');
    }

    private function _writeSpreadsheetExport(string $path, array $rows, array $headers, string $format): array
    {
        $response = new CraftResponse();
        $response->charset = 'UTF-8';
        $response->data = $rows;

        $formatter = match ($format) {
            'xlsx' => $this->_createXlsxFormatter(),
            'text' => Craft::createObject(CsvResponseFormatter::class, [
                'delimiter' => "\t",
                'contentType' => 'text/plain',
            ]),
            default => Craft::createObject(CsvResponseFormatter::class),
        };

        if ($rows === [] && $headers !== []) {
            $formatter->headers = $headers;
        }

        $formatter->format($response);

        if (@file_put_contents($path, $response->content) === false) {
            throw new \RuntimeException(Craft::t('formie', 'Unable to create export file.'));
        }

        return [
            'path' => $path,
            'filename' => basename($path),
            'mimeType' => match ($format) {
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text' => 'text/plain',
                default => 'text/csv',
            },
        ];
    }

    private function _createXlsxFormatter(): BaseSpreadsheetResponseFormatter
    {
        if (!class_exists('craft\\web\\XlsxResponseFormatter')) {
            throw new \RuntimeException(Craft::t('formie', 'XLSX export requires Craft CMS 5.9 or later.'));
        }

        return Craft::createObject('craft\\web\\XlsxResponseFormatter');
    }

    private function _writeJsonExport(string $path, array $rows): array
    {
        file_put_contents($path, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '[]');

        return [
            'path' => $path,
            'filename' => basename($path),
            'mimeType' => 'application/json',
        ];
    }

    private function _writeXmlExport(string $path, array $rows): array
    {
        $xml = new \SimpleXMLElement('<report/>');

        foreach ($rows as $row) {
            $submission = $xml->addChild('submission');

            foreach ($row as $header => $value) {
                $child = $submission->addChild('column', htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8'));
                $child->addAttribute('name', (string)$header);
            }
        }

        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;
        $dom->save($path);

        return [
            'path' => $path,
            'filename' => basename($path),
            'mimeType' => 'application/xml',
        ];
    }


    // Private Methods
    // =========================================================================

    private function _normaliseRows(array $rows): array
    {
        if (!$rows) {
            return [];
        }

        $counts = array_map('count', $rows);
        $key = array_flip($counts)[max($counts)];
        $template = array_fill_keys(array_keys($rows[$key]), '');

        return array_map(fn(array $row) => array_merge($template, $row), $rows);
    }

    private function _resolveExtension(string $format): string
    {
        return match ($format) {
            'json' => 'json',
            'xml' => 'xml',
            'text' => 'txt',
            'xlsx' => 'xlsx',
            default => 'csv',
        };
    }

    private function _sanitizeFilenameSegment(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return 'report';
        }

        return $this->_sanitizeFilename($value);
    }

    private function _sanitizeFilename(string $value): string
    {
        $value = preg_replace('/\.[a-z0-9]{1,8}$/i', '', $value) ?: $value;
        $value = FileHelper::sanitizeFilename($value, [
            'separator' => '-',
        ]);

        return $value !== '' ? $value : 'formie-report';
    }
}
