<?php

use verbb\formie\Formie;

it('preserves distinct export columns with repeated labels', function (string $format, string $variant): void {
    $form = formie()->form()->singleLineTextField('first')->singleLineTextField('second')->singleLineTextField('third')->create();
    formie()->submission($form)->with(['first' => 'Alpha', 'second' => 'Beta', 'third' => 'Gamma'])->save();
    $labels = match ($variant) {
        'duplicate' => ['Value', 'Value', 'Value'],
        'suffix' => ['Value', 'Value', 'Value (2)'],
        'numeric' => ['0', '1', '2'],
        default => ['Value', 'Second', 'Third'],
    };
    $columns = [
        ['type' => 'field', 'handle' => 'first', 'label' => $labels[0], 'enabled' => true],
        ['type' => 'field', 'handle' => 'second', 'label' => $labels[1], 'enabled' => true],
        ['type' => 'field', 'handle' => 'third', 'label' => $labels[2], 'enabled' => true],
    ];
    $report = new \verbb\formie\models\Report(['name' => 'Duplicate headers', 'handle' => 'duplicateHeaders' . uniqid()]);
    $report->setSettingsModel(\verbb\formie\models\ReportSettings::fromArray(['filters' => ['formIds' => [$form->id]]]));
    $query = Formie::$plugin->getReportQuery()->buildSubmissionQuery($report, new \craft\elements\User(['admin' => true]));
    $result = Formie::$plugin->getReportExport()->export($report, $format, $query, 100, $columns);
    try {
        if ($format === 'csv' || $format === 'text') {
            $stream = fopen($result['path'], 'rb');
            if ($format === 'csv') { fread($stream, 3); }
            $delimiter = $format === 'text' ? "\t" : ',';
            $headers = fgetcsv($stream, null, $delimiter, '"', '');
            $row = fgetcsv($stream, null, $delimiter, '"', '');
            fclose($stream);
        } elseif ($format === 'xlsx') {
            $reader = new \OpenSpout\Reader\XLSX\Reader();
            $reader->open($result['path']);
            $rows = [];
            try { foreach ($reader->getSheetIterator() as $sheet) { foreach ($sheet->getRowIterator() as $item) { $rows[] = $item->toArray(); } } }
            finally { $reader->close(); }
            $headers = $rows[0];
            $row = $rows[1];
        } elseif ($format === 'xml') {
            $xml = simplexml_load_file($result['path']);
            $headers = array_map(static fn($column) => (string)$column['name'], $xml->xpath('/report/submission/column'));
            $row = array_map(static fn($column) => (string)$column, $xml->xpath('/report/submission/column'));
        } else {
            $record = json_decode(file_get_contents($result['path']), false, 512, JSON_THROW_ON_ERROR)[0];
            expect($record)->toBeInstanceOf(stdClass::class);
            $headers = array_map('strval', array_keys((array)$record));
            $row = array_values((array)$record);
        }
        $expectedHeaders = match ($variant) {
            'duplicate' => ['Value', 'Value (2)', 'Value (3)'],
            'suffix' => ['Value', 'Value (3)', 'Value (2)'],
            default => $labels,
        };
        expect($headers)->toBe($expectedHeaders);
        expect($row)->toBe(['Alpha', 'Beta', 'Gamma']);
    } finally { unlink($result['path']); }
})->with(['csv', 'text', 'xlsx', 'json', 'xml'])->with(['duplicate', 'suffix', 'numeric', 'unique']);
