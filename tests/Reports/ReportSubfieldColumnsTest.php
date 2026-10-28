<?php

use craft\elements\User;
use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Address;
use verbb\formie\fields\Name;
use verbb\formie\models\Report;
use verbb\formie\models\ReportSettings;

it('offers enabled name and address parts without changing automatic report columns', function (): void {
    $rows = (new Name())->getSubFields();
    $rows[0]['fields'][1]['label'] = 'Given name';
    $form = formie()->form()
        ->nameField('person', ['label' => 'Person', 'useMultipleFields' => true, 'rows' => $rows])
        ->addressField('address', ['label' => 'Address', 'rows' => (new Address())->getSubFields()])
        ->nameField('singleName')
        ->create();
    $service = Formie::$plugin->getReportColumns();
    $admin = new User(['admin' => true]);
    $columns = $service->getFieldColumnsForFormIds([$form->id], $admin);
    $byHandle = array_column($columns, null, 'handle');

    expect($byHandle)->toHaveKeys(['person', 'person.firstName', 'person.lastName', 'address.city', 'address.country', 'singleName'])
        ->not->toHaveKeys(['person.middleName', 'person.prefix', 'address.address2', 'singleName.firstName'])
        ->and($byHandle['person.firstName']['label'])->toBe('Person: Given name')
        ->and($byHandle['address.city']['label'])->toBe('Address: City');
    $groups = $service->getFieldColumnGroupsForFormIds([$form->id], $admin);
    expect(array_column($groups[0]['columns'], 'handle'))->toBe(array_column($columns, 'handle'))
        ->and($groups[0]['columns'][0]['formId'])->toBe((int)$form->id)
        ->and($service->getFieldColumnsForFormIds([$form->id], new User()))->toBe([])
        ->and($service->getFieldColumnGroupsForFormIds([$form->id], new User()))->toBe([]);

    $report = new Report();
    $report->setSettingsModel(ReportSettings::fromArray(['filters' => ['formIds' => [$form->id]]]));
    $automatic = array_filter($service->resolveColumns($report, user: $admin), fn($column) => $column['type'] === 'field');
    expect(array_column($automatic, 'handle'))->toEqualCanonicalizing(['person', 'address', 'singleName']);
});

it('exports saved subfield selections with stable empty cells and custom ordering', function (string $format, bool $useLabels): void {
    $addressRows = (new Address())->getSubFields();
    foreach ($addressRows as &$row) {
        foreach ($row['fields'] as &$child) {
            if ($child['handle'] === 'country') {
                $child['optionValue'] = 'short';
            }
        }
        unset($child);
    }
    unset($row);
    $form = formie()->form()
        ->nameField('person', ['label' => 'Person', 'useMultipleFields' => true, 'rows' => (new Name())->getSubFields()])
        ->addressField('address', ['label' => 'Address', 'rows' => $addressRows])
        ->create();
    $submission = formie()->submission($form)->with([
        'person' => ['firstName' => 'Jane', 'lastName' => 'Smith'],
        'address' => ['address1' => '123 Main St', 'city' => '', 'zip' => '0', 'state' => 'ON', 'country' => 'CA'],
    ])->save();
    $submission = Submission::find()->id($submission->id)->one();
    $service = Formie::$plugin->getReportColumns();
    $catalog = array_column($service->getFieldColumnsForFormIds([$form->id], new User(['admin' => true])), null, 'handle');
    $handles = ['person.lastName', 'person.firstName', 'address.city', 'address.zip', 'address.address1', 'address.country'];
    $selected = array_map(function ($handle) use ($catalog) {
        return [...$catalog[$handle], 'enabled' => true];
    }, $handles);
    $report = new Report(['name' => 'Subfields', 'handle' => 'subfields' . uniqid()]);
    Formie::$plugin->getReportEditor()->applyPayload($report, [
        'filters' => ['formIds' => [$form->id]],
        'columns' => $selected,
        'display' => ['fieldColumnsMode' => 'selected', 'useOptionLabels' => $useLabels],
    ]);
    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();
    $report = Formie::$plugin->getReports()->getReportById($report->id);
    $columns = $service->resolveColumns($report);
    $expected = ['Smith', 'Jane', '', '0', '123 Main St', $useLabels ? 'Canada' : 'CA'];
    $cells = $service->formatRow($submission, $columns, $report->getSettingsModel()->display);
    expect(array_column($columns, 'handle'))->toBe($handles)
        ->and(array_map(fn($cell) => (string)$cell, $cells))->toBe($expected)
        ->and(array_column($service->formatViewerRow($submission, $columns, $report->getSettingsModel()->display), 'value'))->toBe($cells);

    $query = Formie::$plugin->getReportQuery()->buildSubmissionQuery($report, new User(['admin' => true]));
    $result = Formie::$plugin->getReportExport()->export($report, $format, $query);
    try {
        if ($format === 'csv' || $format === 'text') {
            $stream = fopen($result['path'], 'rb');
            if ($format === 'csv') {
                fread($stream, 3);
            }
            $delimiter = $format === 'csv' ? ',' : "\t";
            $headers = fgetcsv($stream, null, $delimiter, '"', '');
            $values = fgetcsv($stream, null, $delimiter, '"', '');
            fclose($stream);
        } elseif ($format === 'xlsx') {
            $reader = new \OpenSpout\Reader\XLSX\Reader();
            $reader->open($result['path']);
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
            [$headers, $values] = $rows;
        } elseif ($format === 'xml') {
            $xml = simplexml_load_file($result['path']);
            $headers = array_map(fn($column) => (string)$column['name'], $xml->xpath('/report/submission/column'));
            $values = array_map(fn($column) => (string)$column, $xml->xpath('/report/submission/column'));
        } else {
            $row = json_decode(file_get_contents($result['path']), true, 512, JSON_THROW_ON_ERROR)[0];
            $headers = array_keys($row);
            $values = array_values($row);
        }
        expect($headers)->toBe(array_column($selected, 'label'))
            ->and(array_map(fn($value) => (string)$value, $values))->toBe($expected);
    } finally {
        unlink($result['path']);
    }
})->with(['csv', 'text', 'xlsx', 'json', 'xml'])->with([true, false]);

it('keeps combined fields and returns empty cells for absent or disabled parts', function (): void {
    $form = formie()->form()->nameField('person', ['useMultipleFields' => true, 'rows' => (new Name())->getSubFields()])->create();
    $submission = formie()->submission($form)->with(['person' => ['firstName' => 'Jane', 'lastName' => 'Smith']])->save();
    $service = Formie::$plugin->getReportColumns();
    $display = ReportSettings::defaultDisplay();
    expect($service->formatCell($submission, ['type' => 'field', 'handle' => 'person'], $display))->toBe('Jane Smith');
    foreach (['person.middleName', 'person.missing', 'person.firstName.extra', 'missing.city'] as $handle) {
        expect($service->formatCell($submission, ['type' => 'field', 'handle' => $handle], $display))->toBeNull();
    }
});

it('resolves shared subfield columns against each submissions form', function (): void {
    $compound = formie()->form()->nameField('person', ['useMultipleFields' => true, 'rows' => (new Name())->getSubFields()])->create();
    $simple = formie()->form()->nameField('person')->create();
    $absent = formie()->form()->singleLineTextField('message')->create();
    $service = Formie::$plugin->getReportColumns();
    $admin = new User(['admin' => true]);
    $catalog = $service->getFieldColumnsForFormIds([$simple->id, $compound->id, $absent->id], $admin);
    expect(array_count_values(array_column($catalog, 'handle'))['person.firstName'])->toBe(1);

    $report = new Report();
    $report->setSettingsModel(ReportSettings::fromArray([
        'display' => ['fieldColumnsMode' => 'selected', 'useFieldHandles' => true],
        'columns' => [['type' => 'field', 'handle' => 'person.firstName', 'label' => 'Given name', 'enabled' => true]],
    ]));
    $columns = $service->resolveColumns($report);
    expect($columns[0]['header'])->toBe('person.firstName');
    foreach ([[$compound, ['firstName' => 'Jane'], 'Jane'], [$simple, 'Jane Smith', null], [$absent, null, null], [$compound, [], null]] as [$form, $value, $expected]) {
        $submission = formie()->submission($form)->with($form === $absent ? [] : ['person' => $value])->save();
        $actual = $service->formatCell($submission, $columns[0], $report->getSettingsModel()->display);
        expect($actual === '' ? null : $actual)->toBe($expected);
    }
});
