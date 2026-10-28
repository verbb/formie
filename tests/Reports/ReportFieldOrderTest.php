<?php

use craft\elements\User;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\models\Report;
use verbb\formie\models\ReportSettings;
use verbb\formie\services\ReportColumns;

function reportFieldOrderForm(): Form
{
    $form = formie()->form()
        ->singleLineTextField('address', ['label' => 'Address'])
        ->page(2)
        ->headingField('heading', ['label' => 'Details'])
        ->singleLineTextField('company', ['label' => 'Company'])
        ->row()
        ->singleLineTextField('firstName', ['label' => 'First Name'])
        ->singleLineTextField('lastName', ['label' => 'Last Name'])
        ->create();

    // Reorder persisted identities so IDs cannot accidentally stand in for layout positions.
    $layout = $form->getFormLayout();
    $pages = array_reverse($layout->getPages());
    foreach ($pages as $page) {
        $rows = array_reverse($page->getRows());
        foreach ($rows as $row) {
            $row->setFields(array_reverse($row->getFields()));
        }
        $page->setRows($rows);
    }
    $layout->setPages($pages);
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    return $form;
}

it('offers report fields in persisted page row and field order', function (string $surface): void {
    $form = reportFieldOrderForm();
    $admin = new User(['admin' => true]);
    $service = Formie::$plugin->getReportColumns();
    $columns = $surface === 'groups'
        ? $service->getFieldColumnGroupsForFormIds([$form->id], $admin)[0]['columns']
        : $service->getFieldColumnsForFormIds([$form->id], $admin);

    expect(array_column($columns, 'handle'))->toBe(['lastName', 'firstName', 'company', 'address']);
})->with(['available', 'groups']);

it('uses layout order in automatic report tables and CSV exports while preserving selected order', function (string $mode): void {
    $form = reportFieldOrderForm();
    formie()->submission($form)->with([
        'lastName' => 'Lovelace', 'firstName' => 'Ada', 'company' => 'Analytical', 'address' => 'London',
    ])->save();
    $report = new Report(['name' => 'Layout order', 'handle' => 'layoutOrder' . uniqid()]);
    $report->setSettingsModel(ReportSettings::fromArray([
        'filters' => ['formIds' => [$form->id]],
        'display' => ['fieldColumnsMode' => $mode],
        'columns' => [
            ['type' => 'attribute', 'handle' => 'id', 'enabled' => false],
            ['type' => 'field', 'handle' => 'company', 'label' => 'Employer', 'enabled' => true],
            ['type' => 'field', 'handle' => 'lastName', 'label' => 'Surname', 'enabled' => true],
        ],
    ]));
    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();
    $report = Formie::$plugin->getReports()->getReportById((int)$report->id);

    WebRequestTestHelper::withWebRequestContext(function () use ($report, $mode): void {
        $admin = User::find()->admin(true)->one();
        Craft::$app->getUser()->setIdentity($admin);
        $automatic = $mode === ReportColumns::FIELD_COLUMNS_MODE_ALL;
        $expectedHandles = $automatic ? ['lastName', 'firstName', 'company', 'address'] : ['company', 'lastName'];
        $expectedHeaders = $automatic ? ['Last Name', 'First Name', 'Company', 'Address'] : ['Employer', 'Surname'];
        $expectedValues = $automatic ? ['Lovelace', 'Ada', 'Analytical', 'London'] : ['Analytical', 'Lovelace'];
        $service = Formie::$plugin->getReportColumns();
        expect(array_column($service->resolveColumns($report, user: $admin), 'handle'))->toBe($expectedHandles);

        $config = Formie::$plugin->getReportViewer()->getViewConfig($report, $admin);
        $table = Formie::$plugin->getReportQuery()->getTableData($report, user: $admin, columnOverride: $config['viewerColumns']);
        expect(array_column($table['columns'], 'handle'))->toBe($expectedHandles);

        // Exercise the direct export path and the viewer's explicit column override.
        foreach ([null, $config['exportColumns']] as $override) {
            $export = Formie::$plugin->getReportExport()->export($report, 'csv', columnOverride: $override);
            try {
                $stream = fopen($export['path'], 'rb');
                try {
                    fread($stream, 3);
                    expect(fgetcsv($stream, null, ',', '"', ''))->toBe($expectedHeaders)
                        ->and(fgetcsv($stream, null, ',', '"', ''))->toBe($expectedValues);
                } finally {
                    fclose($stream);
                }
            } finally {
                unlink($export['path']);
            }
        }
    });
})->with([ReportColumns::FIELD_COLUMNS_MODE_ALL, ReportColumns::FIELD_COLUMNS_MODE_SELECTED]);

it('keeps form ordering and shared handle deduplication when reports span multiple forms', function (): void {
    $first = reportFieldOrderForm();
    $second = formie()->form()
        ->singleLineTextField('company', ['label' => 'Shared Company'])
        ->singleLineTextField('zebra', ['label' => 'Zebra'])
        ->singleLineTextField('alpha', ['label' => 'Alpha'])
        ->create();
    $service = Formie::$plugin->getReportColumns();
    $admin = new User(['admin' => true]);
    $columns = $service->getFieldColumnsForFormIds([$second->id, $first->id], $admin);

    expect(array_column($columns, 'handle'))->toBe(['lastName', 'firstName', 'company', 'address', 'zebra', 'alpha'])
        ->and($columns[2]['label'])->toBe('Company');
    $groups = $service->getFieldColumnGroupsForFormIds([$first->id, $second->id], $admin);
    $groupsByForm = array_column($groups, 'columns', 'formId');
    expect(array_column($groupsByForm[$second->id], 'handle'))->toBe(['company', 'zebra', 'alpha']);
});

it('persists each field order independently of field inclusion for tables and exports', function (string $mode, string $order): void {
    $form = reportFieldOrderForm();
    $submission = formie()->submission($form)->with([
        'lastName' => 'Lovelace', 'firstName' => 'Ada', 'company' => 'Analytical', 'address' => 'London',
    ])->save();
    $columns = [
        ['type' => 'field', 'handle' => 'address', 'label' => 'Address', 'enabled' => true],
        ['type' => 'attribute', 'handle' => 'id', 'label' => 'ID', 'enabled' => true],
        ['type' => 'field', 'handle' => 'lastName', 'label' => 'Last Name', 'enabled' => true],
        ['type' => 'field', 'handle' => 'company', 'label' => 'Company', 'enabled' => true],
    ];
    $report = new Report(['name' => 'Field order modes', 'handle' => 'fieldOrderModes' . uniqid()]);
    Formie::$plugin->getReportEditor()->applyPayload($report, [
        'filters' => ['formIds' => [$form->id]],
        'columns' => $columns,
        'display' => ['fieldColumnsMode' => $mode, 'fieldOrder' => $order],
    ]);
    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();
    $report = Formie::$plugin->getReports()->getReportById((int)$report->id);
    $all = $mode === 'all';
    $expected = match ($order) {
        'layout' => $all ? ['lastName', 'id', 'firstName', 'company', 'address'] : ['lastName', 'id', 'company', 'address'],
        'alphabetical' => $all ? ['address', 'id', 'company', 'firstName', 'lastName'] : ['address', 'id', 'company', 'lastName'],
        default => $all ? ['address', 'id', 'lastName', 'company', 'firstName'] : ['address', 'id', 'lastName', 'company'],
    };
    $labels = ['lastName' => 'Last Name', 'firstName' => 'First Name', 'company' => 'Company', 'address' => 'Address', 'id' => 'ID'];
    $values = ['lastName' => 'Lovelace', 'firstName' => 'Ada', 'company' => 'Analytical', 'address' => 'London', 'id' => (string)$submission->id];
    expect(Formie::$plugin->getReportEditor()->getEditorValues($report)['display']['fieldOrder'])->toBe($order);

    WebRequestTestHelper::withWebRequestContext(function () use ($report, $expected, $labels, $values): void {
        $admin = User::find()->admin(true)->one();
        Craft::$app->getUser()->setIdentity($admin);
        $config = Formie::$plugin->getReportViewer()->getViewConfig($report, $admin);
        expect(array_column($config['viewerColumns'], 'handle'))->toBe($expected);
        $table = Formie::$plugin->getReportQuery()->getTableData($report, user: $admin, columnOverride: $config['viewerColumns']);
        expect(array_column($table['columns'], 'handle'))->toBe($expected);

        foreach ([null, $config['exportColumns']] as $override) {
            $export = Formie::$plugin->getReportExport()->export($report, 'csv', columnOverride: $override);
            try {
                $stream = fopen($export['path'], 'rb');
                try {
                    fread($stream, 3);
                    expect(fgetcsv($stream, null, ',', '"', ''))->toBe(array_map(fn($handle) => $labels[$handle], $expected))
                        ->and(fgetcsv($stream, null, ',', '"', ''))->toBe(array_map(fn($handle) => $values[$handle], $expected));
                } finally {
                    fclose($stream);
                }
            } finally {
                unlink($export['path']);
            }
        }
    });
})->with(['all', 'selected'])->with(['layout', 'alphabetical', 'custom']);

it('preserves legacy selected order through an editor save and defaults automatic reports to layout', function (string $mode): void {
    $form = reportFieldOrderForm();
    $report = new Report(['name' => 'Legacy field order', 'handle' => 'legacyFieldOrder' . uniqid()]);
    $report->setSettingsModel(ReportSettings::fromArray([
        'filters' => ['formIds' => [$form->id]],
        'display' => ['fieldColumnsMode' => $mode],
        'columns' => [
            ['type' => 'attribute', 'handle' => 'id', 'label' => 'ID', 'enabled' => false],
            ['type' => 'field', 'handle' => 'company', 'label' => 'Employer', 'enabled' => true],
            ['type' => 'field', 'handle' => 'lastName', 'label' => 'Surname', 'enabled' => true],
        ],
    ]));
    $editor = Formie::$plugin->getReportEditor();
    $values = $editor->getEditorValues($report);
    expect($values['display']['fieldOrder'])->toBe($mode === 'selected' ? 'custom' : 'layout');
    $editor->applyPayload($report, $values);
    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();
    $report = Formie::$plugin->getReports()->getReportById((int)$report->id);
    $columns = Formie::$plugin->getReportColumns()->resolveColumns($report, user: new User(['admin' => true]));
    expect(array_column($columns, 'handle'))->toBe($mode === 'selected' ? ['company', 'lastName'] : ['lastName', 'firstName', 'company', 'address']);
    if ($mode === 'selected') {
        expect(array_column($columns, 'header'))->toBe(['Employer', 'Surname']);
    }
})->with(['all', 'selected']);

it('normalizes invalid field order without losing a legacy selected order', function (): void {
    $service = Formie::$plugin->getReportColumns();
    expect($service->inferFieldOrder([], ['fieldColumnsMode' => 'selected', 'fieldOrder' => 'invalid']))->toBe('custom')
        ->and($service->inferFieldOrder([], ['fieldColumnsMode' => 'all', 'fieldOrder' => ['invalid']]))->toBe('layout');
});

it('follows later layout changes automatically and appends new fields to custom all-field reports', function (string $order): void {
    $form = reportFieldOrderForm();
    $report = new Report();
    $report->setSettingsModel(ReportSettings::fromArray([
        'filters' => ['formIds' => [$form->id]],
        'display' => ['fieldColumnsMode' => 'all', 'fieldOrder' => $order],
        'columns' => [
            ['type' => 'attribute', 'handle' => 'id', 'enabled' => false],
            ...array_map(fn($handle) => ['type' => 'field', 'handle' => $handle, 'label' => $form->getFieldByHandle($handle)->label, 'enabled' => true], ['company', 'lastName', 'firstName', 'address']),
        ],
    ]));
    $service = Formie::$plugin->getReportColumns();
    $admin = new User(['admin' => true]);
    $before = array_column($service->resolveColumns($report, user: $admin), 'handle');
    $row = $form->getFormLayout()->getPages()[0]->getRows()[0];
    $row->setFields([
        new \verbb\formie\fields\SingleLineText(['handle' => 'added', 'label' => 'A new field']),
        ...array_reverse($row->getFields()),
    ]);
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
    $after = array_column($service->resolveColumns($report, user: $admin), 'handle');
    $expected = match ($order) {
        'layout' => ['added', 'firstName', 'lastName', 'company', 'address'],
        'alphabetical' => ['added', 'address', 'company', 'firstName', 'lastName'],
        default => [...$before, 'added'],
    };
    expect($after)->toBe($expected);
})->with(['layout', 'alphabetical', 'custom']);
