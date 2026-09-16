<?php

declare(strict_types=1);

use craft\elements\User;
use craft\helpers\Db;
use verbb\formie\Formie;
use verbb\formie\models\{Report, ReportSettings};

it('sorts report pages and exports by the displayed form name', function (string $direction, bool $regional): void {
    $sites = Craft::$app->getSites()->getAllSiteIds();
    if ($regional && count($sites) < 2) { $this->markTestSkipped('Multi-site contract.'); }
    $siteId = $sites[$regional ? 1 : 0];
    $formIds = [];
    $ids = [];
    foreach (['Zulu', 'Alpha', 'Bravo', 'Alpha'] as $index => $name) {
        $form = formie()->form(['title' => $name, 'siteId' => $siteId, 'sourceSiteId' => $siteId])->create();
        $submission = new \verbb\formie\elements\Submission(['siteId' => $siteId, 'title' => 'Sorted row']);
        $submission->setForm($form);
        expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();
        Db::update('{{%elements}}', ['dateCreated' => '2026-09-15 12:00:0' . $index], ['id' => $submission->id]);
        $formIds[] = $form->id;
        $ids[] = (int)$submission->id;
    }
    $expected = $direction === 'asc' ? [$ids[1], $ids[3], $ids[2], $ids[0]] : [$ids[0], $ids[2], $ids[1], $ids[3]];
    $report = new Report(['name' => 'Name sort', 'handle' => 'nameSort' . uniqid()]);
    $report->setSettingsModel(ReportSettings::fromArray([
        'filters' => ['formIds' => $formIds], 'display' => ['fieldColumnsMode' => 'none'],
        'columns' => [['type' => 'attribute', 'handle' => 'id', 'label' => 'ID', 'enabled' => true]],
    ]));
    $service = Formie::$plugin->getReportQuery();
    $admin = new User(['admin' => true]);
    $viewer = ['sort' => 'formName', 'sortDir' => $direction];
    $first = $service->getTableData($report, page: 1, limit: 2, user: $admin, viewer: $viewer);
    $second = $service->getTableData($report, page: 2, limit: 2, user: $admin, viewer: $viewer);
    expect(array_column(array_merge($first['rows'], $second['rows']), 'id'))->toBe($expected);
    expect($first['pagination']['total'])->toBe(4);
    $export = Formie::$plugin->getReportExport()->export($report, 'json', $service->buildViewerQuery($report, $admin, $viewer));
    try {
        expect(array_map('intval', array_column(json_decode(file_get_contents($export['path']), true), 'ID')))->toBe($expected);
    } finally { unlink($export['path']); }
})->with(['asc', 'desc'])->with(['primary' => false, 'regional' => true]);
