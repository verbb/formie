<?php

declare(strict_types=1);

use craft\elements\User;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\models\Report;
use verbb\formie\models\ReportSettings;
use verbb\formie\services\ReportColumns;

it('keeps automatic form fields in the viewer table and export selection', function (string $mode): void {
    $form = formie()->form()->singleLineTextField('answer')->create();
    $submission = formie()->submission($form)->with(['answer' => 'A saved answer'])->save();
    $report = new Report(['name' => 'Viewer columns', 'handle' => 'viewerColumns' . uniqid()]);
    $report->setSettingsModel(ReportSettings::fromArray([
        'filters' => ['formIds' => [$form->id]],
        'display' => ['fieldColumnsMode' => $mode],
        'columns' => [
            ['type' => 'attribute', 'handle' => 'title', 'label' => 'Submission title', 'enabled' => true],
            ['type' => 'attribute', 'handle' => 'id', 'label' => 'ID', 'enabled' => false],
        ],
    ]));
    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();

    WebRequestTestHelper::withWebRequestContext(function () use ($report, $mode): void {
        $admin = User::find()->admin(true)->one();
        Craft::$app->getUser()->setIdentity($admin);
        $config = Formie::$plugin->getReportViewer()->getViewConfig($report, $admin);
        $expected = $mode === ReportColumns::FIELD_COLUMNS_MODE_ALL ? ['title', 'id', 'answer'] : ['title', 'id'];
        expect(array_column($config['viewerColumns'], 'handle'))->toBe($expected)
            ->and($config['exportColumns'])->toBe($config['viewerColumns'])
            ->and($config['viewerColumns'][1]['enabled'])->toBeFalse();
        $table = Formie::$plugin->getReportQuery()->getTableData($report, user: $admin, columnOverride: $config['viewerColumns']);
        expect(array_column($table['columns'], 'handle'))->toBe(array_values(array_diff($expected, ['id'])));
        $export = Formie::$plugin->getReportExport()->export($report, 'csv', columnOverride: $config['exportColumns']);
        try {
            $contents = file_get_contents($export['path']);
            if ($mode === ReportColumns::FIELD_COLUMNS_MODE_ALL) {
                expect($contents)->toContain('A saved answer');
            } else {
                expect($contents)->not->toContain('A saved answer');
            }
        } finally {
            unlink($export['path']);
        }
    });
})->with([ReportColumns::FIELD_COLUMNS_MODE_ALL, ReportColumns::FIELD_COLUMNS_MODE_SELECTED]);
