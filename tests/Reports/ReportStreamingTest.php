<?php

use craft\elements\User;
use verbb\formie\Formie;
use verbb\formie\helpers\ReportExportRows;
use verbb\formie\helpers\ReportExportWriter;
use verbb\formie\jobs\ExportReport;
use verbb\formie\models\{Report, ReportExportFile, ReportSettings};
use verbb\formie\services\{ReportExport, ReportExportFiles};

it('streams every format while preserving text and escaping markup and formulas', function (string $format): void {
    $path = tempnam(Craft::$app->getPath()->getTempPath(), 'stream-');
    $values = ['00123', '=1+1', '  @SUM(A1)', "A & <B> \"quoted\"\nnext", '日本語'];
    $rows = (function () use ($values) { foreach ($values as $value) { yield ['Value' => $value]; } })();
    try {
        ReportExportWriter::write($path, $format, ['Value'], $rows);
        if ($format === 'json') {
            expect(array_column(json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR), 'Value'))->toBe($values);
        } elseif ($format === 'xml') {
            $xml = simplexml_load_file($path);
            expect(array_map(fn($node) => (string)$node, $xml->xpath('/report/submission/column')))->toBe($values);
        } elseif ($format === 'xlsx') {
            $reader = new \OpenSpout\Reader\XLSX\Reader();
            $reader->open($path);
            $actual = [];
            try {
                foreach ($reader->getSheetIterator() as $sheet) {
                    foreach ($sheet->getRowIterator() as $row) { $actual[] = $row->toArray()[0]; }
                }
            } finally { $reader->close(); }
            expect($actual)->toBe(['Value', ...$values]);
            $zip = new ZipArchive();
            $zip->open($path);
            expect($zip->getFromName('xl/worksheets/sheet1.xml'))->not->toContain('<f>');
            $zip->close();
            expect(is_dir($path . '.parts'))->toBeFalse();
        } else {
            $handle = fopen($path, 'rb');
            if ($format === 'csv') { fread($handle, 3); }
            $actual = [];
            while (($row = fgetcsv($handle, null, $format === 'text' ? "\t" : ',', '"', '')) !== false) { $actual[] = $row[0]; }
            fclose($handle);
            expect($actual)->toBe(['Value', '00123', "'=1+1", "'  @SUM(A1)", $values[3], $values[4]]);
        }
    } finally { @unlink($path); }
})->with(['csv', 'text', 'json', 'xml', 'xlsx']);

it('rejects invalid encoded values instead of returning an empty successful export', function (string $format): void {
    $path = tempnam(Craft::$app->getPath()->getTempPath(), 'invalid-stream-');
    try {
        expect(fn() => ReportExportWriter::write($path, $format, ['Value'], [['Value' => "bad\xFF"]]))->toThrow(Exception::class);
    } finally { @unlink($path); }
})->with(['json', 'xml']);

it('preserves snapshot ordering and reports progress as submissions change between batches', function (): void {
    $form = formie()->form()->singleLineTextField('message')->create();
    $ids = [];
    foreach (['One', 'Two', 'Three'] as $value) { $ids[] = (int)formie()->submission($form)->with(['message' => $value])->save()->id; }
    $report = new Report(['name' => 'Snapshot', 'handle' => 'snapshot' . uniqid()]);
    $report->setSettingsModel(ReportSettings::fromArray(['filters' => ['formIds' => [$form->id]]]));
    $query = Formie::$plugin->getReportQuery()->buildSubmissionQuery($report, new User(['admin' => true]));
    $query->orderBy(['elements.id' => SORT_ASC]);
    $progress = [];
    $rows = ReportExportRows::iterate($query, [['type' => 'attribute', 'handle' => 'id', 'header' => 'ID']], [], 1, function ($value) use (&$progress) { $progress[] = $value; });
    $actual = [];
    foreach ($rows as $row) {
        $actual[] = (int)$row['ID'];
        if (count($actual) === 1) {
            Craft::$app->getElements()->deleteElementById($ids[1]);
            formie()->submission($form)->with(['message' => 'Later'])->save();
        }
    }
    expect($actual)->toBe([$ids[0], $ids[2]])->and($progress)->toBe([1 / 3, 2 / 3, 1]);
});

it('retains completed queue output when notifications fail or jobs are retried', function (): void {
    $report = new Report(['name' => 'Queue recovery', 'handle' => 'queueRecovery' . uniqid()]);
    $report->setSettingsModel(new ReportSettings());
    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();
    $files = Formie::$plugin->getReportExportFiles();
    $export = $files->createPending($report, 'json', [], notifyEmail: 'captured@example.test');
    $original = Formie::$plugin->getReportExport();
    $fake = new class extends ReportExport {
        public int $builds = 0;
        public int $notifications = 0;
        public function runQueuedExport(ReportExportFile $export, ?callable $progressCallback = null): array {
            $this->builds++;
            $path = tempnam(Craft::$app->getPath()->getTempPath(), 'queue-recovery-');
            file_put_contents($path, '[]');
            $progressCallback(0.5);
            return ['path' => $path, 'filename' => 'report.json', 'mimeType' => 'application/json'];
        }
        public function sendReadyNotification(ReportExportFile $export): bool { $this->notifications++; throw new RuntimeException('Synthetic notification failure'); }
    };
    Formie::$plugin->set('reportExport', $fake);
    $queue = new \yii\queue\sync\Queue();
    try {
        $job = new ExportReport(['exportFileId' => $export->id]);
        $job->execute($queue);
        $first = $files->getExportFileById($export->id);
        $job->execute($queue);
        $second = $files->getExportFileById($export->id);
        expect($first->status)->toBe(ReportExportFile::STATUS_READY)
            ->and($second->downloadTokenHash)->toBe($first->downloadTokenHash)
            ->and($second->filePath)->toBe($first->filePath)
            ->and($fake->builds)->toBe(1)->and($fake->notifications)->toBe(1);
    } finally { Formie::$plugin->set('reportExport', $original); $files->deleteExportFile($files->getExportFileById($export->id)); }
});

it('does not announce ready exports when files or status writes are missing', function (): void {
    $files = new class extends ReportExportFiles { public function saveExportFile(ReportExportFile $file): bool { return false; } };
    $file = new ReportExportFile(['reportId' => 1, 'uid' => 'example']);
    expect(fn() => $files->markReady($file, '/nonexistent/export.csv', 'export.csv', 'text/csv'))->toThrow(RuntimeException::class, 'unavailable');
    expect(fn() => $files->markRunning($file))->toThrow(RuntimeException::class, 'save');
    $path = tempnam(Craft::$app->getPath()->getTempPath(), 'status-failure-');
    try { expect(fn() => $files->markReady($file, $path, 'export.csv', 'text/csv'))->toThrow(RuntimeException::class, 'save'); }
    finally { unlink($path); }
});

it('restarts an interrupted queued export and removes its abandoned spreadsheet parts', function (): void {
    $report = new Report(['name' => 'Interrupted export', 'handle' => 'interrupted' . uniqid()]);
    $report->setSettingsModel(new ReportSettings());
    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();
    $files = Formie::$plugin->getReportExportFiles();
    $export = $files->createPending($report, 'json', [], source: ReportExportFile::SOURCE_SCHEDULED);
    $files->markRunning($export);
    $workspace = $files->getWorkingDirectory($export);
    \craft\helpers\FileHelper::createDirectory($workspace . '/export.xlsx.parts');
    file_put_contents($workspace . '/export.xlsx.parts/abandoned', 'private unfinished rows');
    file_put_contents($workspace . '/export.json', '[{"unfinished":');
    try {
        (new ExportReport(['exportFileId' => $export->id]))->execute(new \yii\queue\sync\Queue());
        $ready = $files->getExportFileById($export->id);
        expect($ready->status)->toBe(ReportExportFile::STATUS_READY)
            ->and(json_decode(file_get_contents($ready->filePath), true, 512, JSON_THROW_ON_ERROR))->toBeArray()
            ->and(is_dir($workspace . '/export.xlsx.parts'))->toBeFalse();
    } finally { $files->deleteExportFile($files->getExportFileById($export->id)); }
    expect(is_dir($workspace))->toBeFalse();
});

it('passes batch progress from the queued export to its caller', function (): void {
    $form = formie()->form()->singleLineTextField('message')->create();
    formie()->submission($form)->save();
    $report = new Report(['name' => 'Progress export', 'handle' => 'progress' . uniqid()]);
    $report->setSettingsModel(ReportSettings::fromArray(['filters' => ['formIds' => [$form->id]]]));
    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();
    $export = new ReportExportFile(['reportId' => $report->id, 'format' => 'json', 'source' => ReportExportFile::SOURCE_SCHEDULED]);
    $progress = [];
    $result = Formie::$plugin->getReportExport()->runQueuedExport($export, function ($value) use (&$progress) { $progress[] = $value; });
    try { expect($progress)->not->toBeEmpty()->and(end($progress))->toBe(1); }
    finally { unlink($result['path']); }
});

class FormieShortWriteStream
{
    public $context;
    private int $remaining = 12;
    public function stream_open($path, $mode, $options, &$openedPath): bool { return true; }
    public function stream_write(string $data): int { $written = min(strlen($data), $this->remaining); $this->remaining -= $written; return $written; }
    public function stream_flush(): bool { return true; }
    public function stream_close(): void {}
}

it('detects short writes in every text export format', function (string $format): void {
    stream_wrapper_register('formieshortwrite', FormieShortWriteStream::class);
    try {
        expect(fn() => ReportExportWriter::write('formieshortwrite://export', $format, ['Value'], [['Value' => str_repeat('large', 50)]]))->toThrow(RuntimeException::class, 'write');
    } finally { stream_wrapper_unregister('formieshortwrite'); }
})->with(['csv', 'text', 'json', 'xml']);

it('preserves native numeric and boolean spreadsheet values while keeping text literal', function (): void {
    $path = tempnam(Craft::$app->getPath()->getTempPath(), 'typed-xlsx-');
    try {
        ReportExportWriter::write($path, 'xlsx', ['Number', 'Boolean', 'Text'], [['Number' => 1.5, 'Boolean' => true, 'Text' => '=1+1']]);
        $reader = new \OpenSpout\Reader\XLSX\Reader();
        $reader->open($path);
        $values = [];
        try { foreach ($reader->getSheetIterator() as $sheet) { foreach ($sheet->getRowIterator() as $row) { $values[] = $row->toArray(); } } }
        finally { $reader->close(); }
        expect($values[1])->toBe([1.5, true, '=1+1']);
    } finally { @unlink($path); }
});
