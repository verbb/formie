<?php

declare(strict_types=1);

use craft\elements\User;
use verbb\formie\Formie;
use verbb\formie\models\Report;
use verbb\formie\models\ReportExportFile;
use verbb\formie\models\ReportSettings;
use verbb\formie\services\ReportColumns;

use DateTime;

it('allows saving an existing report without changing its handle', function (): void {
    $handle = 'existingReport' . uniqid();

    $report = new Report([
        'name' => 'Existing Report',
        'handle' => $handle,
    ]);
    $report->setSettingsModel(new ReportSettings());

    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue()
        ->and($report->validate())->toBeTrue();

    $report->name = 'Existing Report Updated';

    expect($report->validate())->toBeTrue()
        ->and(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();

    $loaded = Formie::$plugin->getReports()->getReportById((int)$report->id);

    expect($loaded)->not->toBeNull()
        ->and($loaded->name)->toBe('Existing Report Updated')
        ->and($loaded->handle)->toBe($handle);
});

it('saves and loads a report with settings', function (): void {
    $settings = new ReportSettings();
    $settings->filters['includeSpam'] = true;

    $report = new Report([
        'name' => 'Weekly Summary',
        'handle' => 'weeklySummary' . uniqid(),
    ]);
    $report->setSettingsModel($settings);

    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue()
        ->and($report->id)->not->toBeNull();

    $loaded = Formie::$plugin->getReports()->getReportById((int)$report->id);

    expect($loaded)->not->toBeNull()
        ->and($loaded->name)->toBe('Weekly Summary')
        ->and($loaded->getSettingsModel()->filters['includeSpam'])->toBeTrue();
});

it('builds a submission query for a report', function (): void {
    $form = formie()
        ->form(['title' => 'Report Query Form'])
        ->singleLineTextField('fullName')
        ->create();

    $settings = new ReportSettings();
    $settings->filters['formIds'] = [$form->id];
    $settings->filters['includeIncomplete'] = false;
    $settings->filters['includeSpam'] = false;

    $report = new Report([
        'name' => 'Form Report',
        'handle' => 'formReport' . uniqid(),
    ]);
    $report->setSettingsModel($settings);

    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();

    $admin = new User();
    $admin->admin = true;

    $query = Formie::$plugin->getReportQuery()->buildSubmissionQuery($report, $admin);
    $summary = Formie::$plugin->getReportQuery()->getSummaryCounts($report, $admin);

    expect($query)->not->toBeNull()
        ->and($summary['forms'])->toHaveCount(1)
        ->and($summary['forms'][0]['formId'])->toBe($form->id);
});

it('resolves enabled report columns in order', function (): void {
    $settings = new ReportSettings();
    $settings->columns = [
        ['type' => 'attribute', 'handle' => 'title', 'label' => 'Submission Title', 'enabled' => true],
        ['type' => 'attribute', 'handle' => 'id', 'label' => 'Submission ID', 'enabled' => true],
        ['type' => 'attribute', 'handle' => 'ipAddress', 'label' => 'IP', 'enabled' => false],
    ];

    $report = new Report([
        'name' => 'Column Report',
        'handle' => 'columnReport' . uniqid(),
    ]);
    $report->setSettingsModel($settings);

    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();

    $columns = Formie::$plugin->getReportColumns()->resolveColumns($report);

    expect($columns)->toHaveCount(2)
        ->and($columns[0]['handle'])->toBe('title')
        ->and($columns[1]['handle'])->toBe('id');
});

it('returns field columns only for the requested forms', function (): void {
    $firstForm = formie()
        ->form(['title' => 'Field Columns Form A'])
        ->singleLineTextField('firstName')
        ->create();

    $secondForm = formie()
        ->form(['title' => 'Field Columns Form B'])
        ->singleLineTextField('lastName')
        ->create();

    $admin = new User();
    $admin->admin = true;

    $firstFormColumns = Formie::$plugin->getReportColumns()->getFieldColumnsForFormIds([$firstForm->id], $admin);
    $secondFormColumns = Formie::$plugin->getReportColumns()->getFieldColumnsForFormIds([$secondForm->id], $admin);

    expect(collect($firstFormColumns)->pluck('handle')->all())->toBe(['firstName'])
        ->and(collect($secondFormColumns)->pluck('handle')->all())->toBe(['lastName']);
});

it('stores only enabled field columns in report settings', function (): void {
    $columns = Formie::$plugin->getReportColumns()->compactColumnsForStorage([
        ['type' => 'attribute', 'handle' => 'title', 'label' => 'Title', 'enabled' => true],
        ['type' => 'field', 'handle' => 'firstName', 'label' => 'First Name', 'enabled' => true, 'formId' => 42],
        ['type' => 'field', 'handle' => 'lastName', 'label' => 'Last Name', 'enabled' => false],
    ]);

    expect(collect($columns)->pluck('handle')->all())->toBe(['title', 'firstName'])
        ->and($columns[1]['formId'] ?? null)->toBe(42);
});

it('omits field columns from storage when using all-fields mode', function (): void {
    $columns = Formie::$plugin->getReportColumns()->compactColumnsForStorage([
        ['type' => 'attribute', 'handle' => 'title', 'label' => 'Title', 'enabled' => true],
        ['type' => 'field', 'handle' => 'firstName', 'label' => 'First Name', 'enabled' => true],
    ], ReportColumns::FIELD_COLUMNS_MODE_ALL);

    expect(collect($columns)->pluck('handle')->all())->toBe(['title']);
});

it('resolves all form fields dynamically when all-fields mode is enabled', function (): void {
    $form = formie()
        ->form(['title' => 'All Fields Mode Form'])
        ->singleLineTextField('dynamicField')
        ->create();

    $settings = new ReportSettings();
    $settings->filters['formIds'] = [$form->id];
    $settings->display['fieldColumnsMode'] = ReportColumns::FIELD_COLUMNS_MODE_ALL;
    $settings->columns = Formie::$plugin->getReportColumns()->getDefaultAttributeColumns();

    $report = new Report([
        'name' => 'All Fields Mode Report',
        'handle' => 'allFieldsModeReport' . uniqid(),
    ]);
    $report->setSettingsModel($settings);

    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();

    $admin = new User();
    $admin->admin = true;

    $columns = Formie::$plugin->getReportColumns()->resolveColumns($report, null, $admin);
    $handles = collect($columns)->pluck('handle')->all();

    expect($handles)->toContain('title')
        ->and($handles)->toContain('dynamicField');
});

it('returns paginated table data for a report', function (): void {
    $form = formie()
        ->form(['title' => 'Report Table Form'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()->submission($form)->with([
        'fullName' => 'Ada Lovelace',
    ])->save();

    $settings = new ReportSettings();
    $settings->filters['formIds'] = [$form->id];
    $settings->columns = [
        ['type' => 'attribute', 'handle' => 'id', 'label' => 'ID', 'enabled' => true],
        ['type' => 'field', 'handle' => 'fullName', 'label' => 'Full Name', 'enabled' => true],
    ];

    $report = new Report([
        'name' => 'Table Report',
        'handle' => 'tableReport' . uniqid(),
    ]);
    $report->setSettingsModel($settings);

    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();

    $admin = new User();
    $admin->admin = true;

    $tableData = Formie::$plugin->getReportQuery()->getTableData($report, 1, 50, $admin);

    expect($tableData['pagination']['total'])->toBeGreaterThanOrEqual(1)
        ->and($tableData['columns'])->toHaveCount(2)
        ->and(collect($tableData['rows'])->pluck('id')->all())->toContain($submission->id);
});

it('applies viewer date range filters consistently to table data and summary counts', function (): void {
    $form = formie()
        ->form(['title' => 'Date Filter Table Form'])
        ->singleLineTextField('fullName')
        ->create();

    formie()->submission($form)->with([
        'fullName' => 'Date Filter Example',
    ])->save();

    $settings = new ReportSettings();
    $settings->filters['formIds'] = [$form->id];
    $settings->columns = Formie::$plugin->getReportColumns()->getDefaultAttributeColumns();

    $report = new Report([
        'name' => 'Date Filter Table Report',
        'handle' => 'dateFilterTableReport' . uniqid(),
    ]);
    $report->setSettingsModel($settings);

    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();

    $admin = new User();
    $admin->admin = true;

    $futureViewer = [
        'startDate' => '2099-01-01 00:00:00',
        'endDate' => '2099-12-31 23:59:59',
    ];

    $futureTableData = Formie::$plugin->getReportQuery()->getTableData($report, 1, 50, $admin, null, $futureViewer);
    $futureSummary = Formie::$plugin->getReportQuery()->getSummaryCounts($report, $admin, null, $futureViewer);

    expect($futureTableData['pagination']['total'])->toBe(0)
        ->and($futureSummary['total'])->toBe(0);

    $inclusiveViewer = [
        'startDate' => '2000-01-01 00:00:00',
        'endDate' => '2099-12-31 23:59:59',
    ];

    $inclusiveTableData = Formie::$plugin->getReportQuery()->getTableData($report, 1, 50, $admin, null, $inclusiveViewer);
    $inclusiveSummary = Formie::$plugin->getReportQuery()->getSummaryCounts($report, $admin, null, $inclusiveViewer);

    expect($inclusiveTableData['pagination']['total'])->toBeGreaterThanOrEqual(1)
        ->and($inclusiveSummary['total'])->toBeGreaterThanOrEqual(1);
});

it('respects viewer column override order in table data', function (): void {
    $form = formie()
        ->form(['title' => 'Ordered Columns Form'])
        ->singleLineTextField('fullName')
        ->create();

    formie()->submission($form)->with([
        'fullName' => 'Ordered Example',
    ])->save();

    $settings = new ReportSettings();
    $settings->filters['formIds'] = [$form->id];
    $settings->columns = [
        ['type' => 'attribute', 'handle' => 'title', 'label' => 'Title', 'enabled' => true],
        ['type' => 'attribute', 'handle' => 'status', 'label' => 'Status', 'enabled' => true],
        ['type' => 'field', 'handle' => 'fullName', 'label' => 'Full Name', 'enabled' => true],
    ];

    $report = new Report([
        'name' => 'Ordered Table Report',
        'handle' => 'orderedTableReport' . uniqid(),
    ]);
    $report->setSettingsModel($settings);

    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();

    $admin = new User();
    $admin->admin = true;

    $columnOverride = [
        ['type' => 'field', 'handle' => 'fullName', 'label' => 'Full Name', 'enabled' => true],
        ['type' => 'attribute', 'handle' => 'status', 'label' => 'Status', 'enabled' => true],
        ['type' => 'attribute', 'handle' => 'title', 'label' => 'Title', 'enabled' => true],
    ];

    $tableData = Formie::$plugin->getReportQuery()->getTableData($report, 1, 50, $admin, $columnOverride);

    expect($tableData['columns'])->toHaveCount(3)
        ->and($tableData['columns'][0]['handle'])->toBe('fullName')
        ->and($tableData['columns'][1]['handle'])->toBe('status')
        ->and($tableData['columns'][2]['handle'])->toBe('title');
});

it('exports report data with viewer column overrides', function (): void {
    $form = formie()
        ->form(['title' => 'Export Form'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()->submission($form)->with([
        'fullName' => 'Export Example',
    ])->save();

    $settings = new ReportSettings();
    $settings->filters['formIds'] = [$form->id];
    $settings->columns = [
        ['type' => 'attribute', 'handle' => 'id', 'label' => 'ID', 'enabled' => true],
        ['type' => 'field', 'handle' => 'fullName', 'label' => 'Full Name', 'enabled' => true],
    ];

    $report = new Report([
        'name' => 'Export Report',
        'handle' => 'exportReport' . uniqid(),
    ]);
    $report->setSettingsModel($settings);

    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();

    $admin = new User();
    $admin->admin = true;

    $columnOverride = [
        ['type' => 'field', 'handle' => 'fullName', 'label' => 'Full Name', 'enabled' => true],
        ['type' => 'attribute', 'handle' => 'id', 'label' => 'ID', 'enabled' => true],
    ];

    $query = Formie::$plugin->getReportQuery()->buildViewerQuery($report, $admin, [
        'search' => '',
        'sort' => 'dateCreated',
        'sortDir' => 'desc',
    ]);

    $export = Formie::$plugin->getReportExport()->export(
        report: $report,
        format: 'csv',
        query: $query,
        columnOverride: $columnOverride,
    );

    expect($export)->toHaveKeys(['path', 'filename', 'mimeType'])
        ->and($export['mimeType'])->toBe('text/csv')
        ->and(is_file($export['path']))->toBeTrue();

    $contents = file_get_contents($export['path']);

    expect($contents)->toContain('Full Name')
        ->and($contents)->toContain('ID')
        ->and($contents)->toContain('Export Example')
        ->and($contents)->toContain((string)$submission->id);

    @unlink($export['path']);
});

it('writes real xlsx exports via Craft spreadsheet formatters', function (): void {
    if (!class_exists('craft\\web\\XlsxResponseFormatter')) {
        test()->markTestSkipped('XLSX export requires Craft CMS 5.9 or later.');
    }

    $form = formie()
        ->form(['title' => 'XLSX Export Form'])
        ->singleLineTextField('fullName')
        ->create();

    formie()->submission($form)->with([
        'fullName' => 'Spreadsheet Example',
    ])->save();

    $settings = new ReportSettings();
    $settings->filters['formIds'] = [$form->id];
    $settings->columns = [
        ['type' => 'field', 'handle' => 'fullName', 'label' => 'Full Name', 'enabled' => true],
    ];

    $report = new Report([
        'name' => 'XLSX Export Report',
        'handle' => 'xlsxExportReport' . uniqid(),
    ]);
    $report->setSettingsModel($settings);

    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();

    $admin = new User();
    $admin->admin = true;

    $export = Formie::$plugin->getReportExport()->export(
        report: $report,
        format: 'xlsx',
        query: Formie::$plugin->getReportQuery()->buildSubmissionQuery($report, $admin),
    );

    expect($export['filename'])->toEndWith('.xlsx')
        ->and($export['mimeType'])->toBe('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ->and(is_file($export['path']))->toBeTrue()
        ->and(file_get_contents($export['path'], false, null, 0, 2))->toBe('PK');

    @unlink($export['path']);
});

it('guards csv exports against spreadsheet formula injection', function (): void {
    $form = formie()
        ->form(['title' => 'CSV Injection Form'])
        ->singleLineTextField('fullName')
        ->create();

    formie()->submission($form)->with([
        'fullName' => '=1+1',
    ])->save();

    $settings = new ReportSettings();
    $settings->filters['formIds'] = [$form->id];
    $settings->columns = [
        ['type' => 'field', 'handle' => 'fullName', 'label' => 'Full Name', 'enabled' => true],
    ];

    $report = new Report([
        'name' => 'CSV Injection Report',
        'handle' => 'csvInjectionReport' . uniqid(),
    ]);
    $report->setSettingsModel($settings);

    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();

    $admin = new User();
    $admin->admin = true;

    $export = Formie::$plugin->getReportExport()->export(
        report: $report,
        format: 'csv',
        query: Formie::$plugin->getReportQuery()->buildSubmissionQuery($report, $admin),
    );

    $contents = file_get_contents($export['path']);

    expect($contents)->toContain("\t=1+1");

    @unlink($export['path']);
});

it('resolves configurable export filenames from report settings', function (): void {
    $settings = new ReportSettings();
    $settings->export = ['filename' => 'weekly-{handle}-{date}'];

    $report = new Report([
        'name' => 'Weekly Sales',
        'handle' => 'weeklySales',
    ]);
    $report->setSettingsModel($settings);

    $filename = Formie::$plugin->getReportExport()->resolveFilename(
        $report,
        'csv',
        new DateTime('2026-06-17 14:30:00'),
    );

    expect($filename)->toBe('weekly-weeklySales-2026-06-17.csv');
});

it('resolves general export filename variables for reports', function (): void {
    $settings = new ReportSettings();
    $settings->export = ['filename' => '{handle}-{timestamp}'];

    $report = new Report([
        'name' => 'Site Export',
        'handle' => 'siteExport',
    ]);
    $report->setSettingsModel($settings);

    $filename = Formie::$plugin->getReportExport()->resolveFilename(
        $report,
        'csv',
        new DateTime('2026-06-17 14:30:00'),
    );

    expect($filename)->toBe('siteExport-2026-06-17-14-30-00.csv');
});

it('fills chart buckets across the resolved date range', function (): void {
    $form = formie()
        ->form(['title' => 'Chart Range Form'])
        ->singleLineTextField('fullName')
        ->create();

    $settings = new ReportSettings();
    $settings->filters['formIds'] = [$form->id];
    $settings->filters['startBound'] = [
        'option' => 'date',
        'date' => '2026-06-01 00:00:00',
        'offset' => 'add',
        'offsetNumber' => 0,
        'offsetType' => 'days',
    ];
    $settings->filters['endBound'] = [
        'option' => 'date',
        'date' => '2026-06-05 23:59:59',
        'offset' => 'add',
        'offsetNumber' => 0,
        'offsetType' => 'days',
    ];

    $report = new Report([
        'name' => 'Chart Range Report',
        'handle' => 'chartRangeReport' . uniqid(),
    ]);
    $report->setSettingsModel($settings);

    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();

    formie()->submission($form)->with(['fullName' => 'Chart Test'])->save();

    $admin = new User();
    $admin->admin = true;

    $chartData = Formie::$plugin->getReportQuery()->getChartData($report, $admin);

    expect($chartData['range']['start'])->toBe('2026-06-01')
        ->and($chartData['range']['end'])->toBe('2026-06-05')
        ->and($chartData['rows'])->toHaveCount(5)
        ->and($chartData['rows'][0])->toHaveKeys(['date', 'complete', 'incomplete', 'spam', 'total'])
        ->and(collect($chartData['rows'])->sum('total'))->toBeGreaterThanOrEqual(1);
});

it('resolves scheduled reports for a report', function (): void {
    $settings = new ReportSettings();

    $report = new Report([
        'name' => 'Scheduled Parent Report',
        'handle' => 'scheduledParent' . uniqid(),
    ]);
    $report->setSettingsModel($settings);

    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();

    $scheduledReport = new \verbb\formie\models\ScheduledReport([
        'name' => 'Weekly summary',
        'reportId' => $report->id,
        'enabled' => true,
    ]);
    $scheduledReport->setDeliveryModel(\verbb\formie\models\ScheduledReportDelivery::fromArray([
        'frequency' => 'weekly',
        'weekday' => 1,
        'hour' => 8,
        'recipients' => ['reports@example.com'],
    ]));

    expect(Formie::$plugin->getScheduledReports()->saveScheduledReport($scheduledReport))->toBeTrue();

    $linked = Formie::$plugin->getScheduledReports()->getScheduledReportsForReport((int)$report->id);

    expect($linked)->toHaveCount(1)
        ->and($linked[0]->name)->toBe('Weekly summary');
});

it('returns empty summary when no forms are selected', function (): void {
    $settings = new ReportSettings();
    $settings->filters['formIds'] = [];

    $report = new Report([
        'name' => 'No Forms Report',
        'handle' => 'noFormsReport' . uniqid(),
    ]);
    $report->setSettingsModel($settings);

    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();

    $admin = new User();
    $admin->admin = true;

    $summary = Formie::$plugin->getReportQuery()->getSummaryCounts($report, $admin);

    expect($summary['total'])->toBe(0)
        ->and($summary['forms'])->toBe([]);
});

it('sets expected default attribute columns', function (): void {
    $columns = Formie::$plugin->getReportColumns()->getDefaultAttributeColumns();
    $columnsByHandle = collect($columns)->keyBy('handle');

    expect(array_column($columns, 'handle'))->toBe([
        'title',
        'formName',
        'status',
        'dateCreated',
        'dateUpdated',
        'id',
        'ipAddress',
        'isIncomplete',
        'isSpam',
    ])
        ->and($columnsByHandle['title']['enabled'])->toBeTrue()
        ->and($columnsByHandle['formName']['enabled'])->toBeTrue()
        ->and($columnsByHandle['id']['enabled'])->toBeFalse()
        ->and($columnsByHandle['ipAddress']['enabled'])->toBeFalse();
});

it('normalizes legacy date-only report filters to date bounds', function (): void {
    $report = new Report([
        'name' => 'Date Filter Report',
        'handle' => 'dateFilterReport' . uniqid(),
    ]);

    $payload = [
        'name' => $report->name,
        'handle' => $report->handle,
        'filters' => [
            'formIds' => [],
            'startDate' => '2026-06-01',
            'endDate' => '2026-06-18',
        ],
        'columns' => [],
        'display' => [],
        'chart' => [],
    ];

    expect(Formie::$plugin->getReportEditor()->applyPayload($report, $payload))->toBeTrue();

    $filters = $report->getSettingsModel()->filters;

    expect($filters['startBound']['option'])->toBe('date')
        ->and($filters['startBound']['date'])->toBe('2026-06-01 00:00:00')
        ->and($filters['endBound']['option'])->toBe('date')
        ->and($filters['endBound']['date'])->toBe('2026-06-18 23:59:59')
        ->and($filters)->not->toHaveKey('startDate')
        ->and($filters)->not->toHaveKey('endDate');
});

it('resolves report date bounds to query datetimes', function (): void {
    $report = new Report([
        'name' => 'Bound Report',
        'handle' => 'boundReport' . uniqid(),
    ]);
    $report->setSettingsModel(ReportSettings::fromArray([
        'filters' => [
            'startBound' => [
                'option' => 'date',
                'date' => '2026-06-01 00:00:00',
            ],
            'endBound' => [
                'option' => 'date',
                'date' => '2026-06-18 23:59:59',
            ],
        ],
    ]));

    $filters = Formie::$plugin->getReportQuery()->resolveFilters($report);

    expect($filters['startDate'])->toBe('2026-06-01 00:00:00')
        ->and($filters['endDate'])->toBe('2026-06-18 23:59:59');
});

it('resolves scheduled report recipients', function (): void {
    $delivery = \verbb\formie\models\ScheduledReportDelivery::fromArray([
        'recipients' => ['one@example.com', 'invalid', ' two@example.com '],
    ]);

    $recipients = Formie::$plugin->getReportScheduledDelivery()->resolveRecipients($delivery);

    expect($recipients)->toBe(['one@example.com', 'two@example.com']);
});

it('stores scheduled report email template id', function (): void {
    $delivery = \verbb\formie\models\ScheduledReportDelivery::fromArray([
        'templateId' => 5,
    ]);

    expect($delivery->templateId)->toBe(5)
        ->and($delivery->toStorageArray()['templateId'])->toBe(5);

    $empty = \verbb\formie\models\ScheduledReportDelivery::fromArray([
        'templateId' => null,
    ]);

    expect($empty->toStorageArray())->not->toHaveKey('templateId');

    $xlsx = \verbb\formie\models\ScheduledReportDelivery::fromArray([
        'format' => 'xlsx',
    ]);

    expect($xlsx->format)->toBe('xlsx')
        ->and($xlsx->toStorageArray()['format'])->toBe('xlsx');
});

it('defaults new report date filters to the last month', function (): void {
    $filters = \verbb\formie\models\ReportSettings::defaultFilters();

    expect($filters['startBound']['option'])->toBe('today')
        ->and($filters['startBound']['offset'])->toBe('subtract')
        ->and($filters['startBound']['offsetNumber'])->toBe(1)
        ->and($filters['startBound']['offsetType'])->toBe('months')
        ->and($filters['endBound']['option'])->toBe('today')
        ->and($filters['endBound']['offset'])->toBe('add')
        ->and($filters['endBound']['offsetNumber'])->toBe(0)
        ->and($filters['endBound']['offsetType'])->toBe('days');
});

it('includes trashed report handles when resolving all report handles', function (): void {
    $handle = 'trashedHandle' . uniqid();

    $report = new Report([
        'name' => 'Trashed Report',
        'handle' => $handle,
    ]);
    $report->setSettingsModel(new ReportSettings());

    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue()
        ->and(Formie::$plugin->getReports()->deleteReport($report))->toBeTrue()
        ->and(Formie::$plugin->getReports()->getReportByHandle($handle))->toBeNull()
        ->and(Formie::$plugin->getReports()->getAllReportHandles())->toContain($handle);
});

it('rejects creating a report with a trashed report handle', function (): void {
    $handle = 'trashedDuplicate' . uniqid();

    $report = new Report([
        'name' => 'Original Report',
        'handle' => $handle,
    ]);
    $report->setSettingsModel(new ReportSettings());

    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue()
        ->and(Formie::$plugin->getReports()->deleteReport($report))->toBeTrue();

    $duplicate = new Report([
        'name' => 'Duplicate Report',
        'handle' => $handle,
    ]);
    $duplicate->setSettingsModel(new ReportSettings());

    expect(Formie::$plugin->getReports()->saveReport($duplicate))->toBeFalse()
        ->and($duplicate->getErrors('handle'))->not->toBeEmpty();
});

it('uses the async export row threshold setting', function (): void {
    $settings = Formie::$plugin->getSettings();
    $original = $settings->reportAsyncExportRowThreshold;
    $settings->reportAsyncExportRowThreshold = 1000;

    expect(Formie::$plugin->getReportExport()->shouldQueueExport(1001))->toBeTrue()
        ->and(Formie::$plugin->getReportExport()->shouldQueueExport(1000))->toBeFalse()
        ->and(Formie::$plugin->getReportExport()->shouldQueueExport(999))->toBeFalse();

    $settings->reportAsyncExportRowThreshold = $original;
});

it('runs queued report exports to a ready download file', function (): void {
    $form = formie()
        ->form(['title' => 'Queued Export Form'])
        ->singleLineTextField('fullName')
        ->create();

    formie()->submission($form)->with(['fullName' => 'Queued Example'])->save();

    $settings = new ReportSettings();
    $settings->filters['formIds'] = [$form->id];
    $settings->columns = [
        ['type' => 'field', 'handle' => 'fullName', 'label' => 'Full Name', 'enabled' => true],
    ];

    $report = new Report([
        'name' => 'Queued Export Report',
        'handle' => 'queuedExportReport' . uniqid(),
    ]);
    $report->setSettingsModel($settings);

    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();

    $exportFile = new \verbb\formie\models\ReportExportFile([
        'reportId' => (int)$report->id,
        'format' => 'csv',
        'context' => Formie::$plugin->getReportExport()->buildExportContext([], [
            ['type' => 'field', 'handle' => 'fullName', 'label' => 'Full Name', 'enabled' => true],
        ]),
    ]);

    $result = Formie::$plugin->getReportExport()->runQueuedExport($exportFile);

    expect($result)->toHaveKeys(['path', 'filename', 'mimeType'])
        ->and(is_file($result['path']))->toBeTrue()
        ->and(file_get_contents($result['path']))->toContain('Queued Example');

    @unlink($result['path']);
});

it('detects exports that exceed the email attachment limit', function (): void {
    $settings = Formie::$plugin->getSettings();
    $original = $settings->maxEmailAttachmentSizeMb;
    $settings->maxEmailAttachmentSizeMb = 1;

    $tempPath = \Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . 'formie-export-limit-test.txt';
    file_put_contents($tempPath, str_repeat('a', 2 * 1024 * 1024));

    expect(Formie::$plugin->getReportExport()->exceedsEmailAttachmentLimit($tempPath))->toBeTrue();

    @unlink($tempPath);
    $settings->maxEmailAttachmentSizeMb = $original;
});

it('hashes report export download tokens at rest', function (): void {
    $service = Formie::$plugin->getReportExportFiles();
    $token = 'test-export-token';

    expect($service->verifyDownloadToken($token, $service->hashDownloadToken($token)))->toBeTrue()
        ->and($service->verifyDownloadToken('wrong-token', $service->hashDownloadToken($token)))->toBeFalse();
});

it('builds report export download urls without craft token param conflict', function (): void {
    $url = ReportExportFile::buildDownloadUrl('export-uid', 'secret-token');

    expect($url)->toContain('downloadToken=')
        ->and($url)->not->toMatch('/([?&])token=/');

    $legacy = 'https://example.com/index.php?p=actions/formie/reports/download-export&uid=export-uid&token=secret-token';

    expect(ReportExportFile::normalizeDownloadUrl($legacy))
        ->toBe('https://example.com/index.php?p=actions/formie/reports/download-export&uid=export-uid&downloadToken=secret-token');
});

it('uses separate cp and signed download urls for interactive exports', function (): void {
    $exportFile = new ReportExportFile([
        'uid' => 'export-uid',
        'source' => ReportExportFile::SOURCE_INTERACTIVE,
        'status' => ReportExportFile::STATUS_READY,
        'downloadUrl' => ReportExportFile::buildDownloadUrl('export-uid', 'secret-token'),
        'downloadTokenHash' => 'abc123',
    ]);

    expect($exportFile->getDownloadUrl())->toContain('downloadToken=secret-token')
        ->and($exportFile->getInteractiveDownloadUrl())->toContain('download-queued-export/export-uid')
        ->and($exportFile->getInteractiveDownloadUrl())->not->toContain('downloadToken=')
        ->and($exportFile->isTokenDownloadable())->toBeTrue();
});

it('invalidates signed links without blocking cp downloads', function (): void {
    $exportFile = new ReportExportFile([
        'uid' => 'export-uid',
        'source' => ReportExportFile::SOURCE_INTERACTIVE,
        'status' => ReportExportFile::STATUS_READY,
        'downloadTokenHash' => hash('sha256', 'secret-token'),
    ]);

    Formie::$plugin->getReportExportFiles()->markConsumed($exportFile);

    expect($exportFile->status)->toBe(ReportExportFile::STATUS_READY)
        ->and($exportFile->isTokenDownloadable())->toBeFalse()
        ->and($exportFile->isDownloadable())->toBeTrue()
        ->and($exportFile->getInteractiveDownloadUrl())->toContain('download-queued-export/export-uid');
});

it('defaults report export download security settings', function (): void {
    $settings = Formie::$plugin->getSettings();

    expect($settings->reportScheduledExportExpiryHours)->toBe(48)
        ->and($settings->reportInteractiveExportExpiryHours)->toBe(72)
        ->and($settings->reportExportSingleUseDownload)->toBeTrue();
});
