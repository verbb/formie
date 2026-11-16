<?php

use craft\elements\User;
use OpenSpout\Reader\XLSX\Reader;
use verbb\formie\Formie;
use verbb\formie\models\Report;
use verbb\formie\models\ReportSettings;
use verbb\formie\services\ReportColumns;

it('exports values longer than an Excel cell holds to xlsx, keeping them whole in other formats', function(): void {
    // A signature drawn with many strokes is stored as a data URI well past Excel's 32,767-character cell limit.
    $signature = 'data:image/png;base64,' . str_repeat('iVBORw0KGgo', 4000);
    // Multibyte characters straddle the limit, so the cut must count characters rather than bytes.
    $notes = str_repeat('a', 32766) . str_repeat('é', 100);

    $form = formie()
        ->form(['title' => 'Long Cell Export'])
        ->singleLineTextField('fullName')
        ->signatureField('signature')
        ->multiLineTextField('notes')
        ->create();

    formie()->submission($form)->with([
        'fullName' => 'Long signature',
        'signature' => $signature,
        'notes' => $notes,
    ])->save();

    $settings = new ReportSettings();
    $settings->filters['formIds'] = [$form->id];
    $settings->display['fieldColumnsMode'] = ReportColumns::FIELD_COLUMNS_MODE_SELECTED;
    $settings->columns = [
        ['type' => 'field', 'handle' => 'fullName', 'label' => 'Full Name', 'enabled' => true],
        ['type' => 'field', 'handle' => 'signature', 'label' => 'Signature', 'enabled' => true],
        ['type' => 'field', 'handle' => 'notes', 'label' => 'Notes', 'enabled' => true],
    ];

    $report = new Report(['name' => 'Long Cell Report', 'handle' => 'longCellReport' . uniqid()]);
    $report->setSettingsModel($settings);
    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();

    $query = static fn() => Formie::$plugin->getReportQuery()->buildSubmissionQuery($report, new User(['admin' => true]));
    $xlsx = Formie::$plugin->getReportExport()->export(report: $report, format: 'xlsx', query: $query());
    $json = Formie::$plugin->getReportExport()->export(report: $report, format: 'json', query: $query());

    try {
        $reader = new Reader();
        $reader->open($xlsx['path']);
        $rows = [];

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rows[] = $row->toArray();
                }
            }
        } finally {
            $reader->close();
        }

        expect($rows)->toHaveCount(2)
            ->and($rows[0])->toBe(['Full Name', 'Signature', 'Notes'])
            ->and($rows[1][0])->toBe('Long signature')
            ->and($rows[1][1])->toBe(mb_substr($signature, 0, 32767))
            ->and($rows[1][2])->toBe(str_repeat('a', 32766) . 'é');

        $record = json_decode(file_get_contents($json['path']), true, 512, JSON_THROW_ON_ERROR)[0];

        expect($record['Signature'])->toBe($signature)
            ->and($record['Notes'])->toBe($notes);
    } finally {
        @unlink($xlsx['path']);
        @unlink($json['path']);
    }
});
