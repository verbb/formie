<?php

declare(strict_types=1);

use craft\elements\User;
use craft\helpers\Db;
use verbb\formie\Formie;
use verbb\formie\models\{Report, ReportSettings};

it('keeps report state selection consistent across table, chart and export', function (array $flags, array $expected): void {
    $form = formie()->form()->singleLineTextField('message')->create();
    $ids = [];
    foreach (['complete' => [false, false], 'incomplete' => [true, false], 'spam' => [false, true], 'incompleteSpam' => [true, true]] as $name => [$incomplete, $spam]) {
        $submission = formie()->submission($form)->with(['message' => $name])->save();
        Db::update('{{%formie_submissions}}', ['isIncomplete' => $incomplete, 'isSpam' => $spam], ['id' => $submission->id]);
        $ids[$name] = (int)$submission->id;
    }
    $settings = ReportSettings::fromArray(['filters' => array_merge(['formIds' => [$form->id]], $flags), 'display' => ['fieldColumnsMode' => 'selected'], 'columns' => [['type' => 'field', 'handle' => 'message', 'label' => 'Message', 'enabled' => true]]]);
    $report = new Report(['name' => 'Selected states', 'handle' => 'selectedStates' . uniqid()]);
    $report->setSettingsModel($settings);
    $service = Formie::$plugin->getReportQuery();
    $admin = new User(['admin' => true]);
    $query = $service->buildSubmissionQuery($report, $admin);
    $actualNames = array_map(fn($row) => $row->getFieldValue('message'), $query->orderBy(['elements.id' => SORT_ASC])->all());
    $chartCount = array_sum(array_column($service->getChartData($report, $admin)['rows'], 'total'));
    $table = $service->getTableData($report, user: $admin);
    $export = Formie::$plugin->getReportExport()->export($report, 'json', clone $query);
    try {
        $exportRows = json_decode(file_get_contents($export['path']), true);
        expect($actualNames)->toBe($expected);
        expect($table['pagination']['total'])->toBe(count($expected));
        expect($chartCount)->toBe(count($expected));
        expect(array_column($exportRows, 'Message'))->toBe($expected);
        expect(array_column($table['rows'], 'id'))->toEqualCanonicalizing(array_map(fn($name) => $ids[$name], $expected));
    } finally { unlink($export['path']); }
})->with([
    'all states' => [['includeComplete' => true, 'includeIncomplete' => true, 'includeSpam' => true], ['complete', 'incomplete', 'spam', 'incompleteSpam']],
    'no states' => [['includeComplete' => false, 'includeIncomplete' => false, 'includeSpam' => false], []],
    'complete only' => [['includeComplete' => true, 'includeIncomplete' => false, 'includeSpam' => false], ['complete']],
    'incomplete only' => [['includeComplete' => false, 'includeIncomplete' => true, 'includeSpam' => false], ['incomplete']],
    'default complete and incomplete' => [['includeComplete' => true, 'includeIncomplete' => true, 'includeSpam' => false], ['complete', 'incomplete']],
    'complete and spam' => [['includeComplete' => true, 'includeIncomplete' => false, 'includeSpam' => true], ['complete', 'spam', 'incompleteSpam']],
    'incomplete and spam' => [['includeComplete' => false, 'includeIncomplete' => true, 'includeSpam' => true], ['incomplete', 'spam', 'incompleteSpam']],
    'spam only' => [['includeComplete' => false, 'includeIncomplete' => false, 'includeSpam' => true], ['spam', 'incompleteSpam']],
]);
