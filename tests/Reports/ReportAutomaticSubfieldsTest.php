<?php

use craft\elements\User;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\fields\Address;
use verbb\formie\fields\Name;
use verbb\formie\models\Report;
use verbb\formie\models\ReportSettings;

it('persists automatic Name and Address modes across report tables and exports', function (string $mode, string $order): void {
    $form = formie()->form()
        ->nameField('person', ['label' => 'Name', 'useMultipleFields' => true, 'rows' => (new Name())->getSubFields()])
        ->addressField('address', ['label' => 'Address', 'rows' => (new Address())->getSubFields()])
        ->nameField('single', ['label' => 'Single name', 'useMultipleFields' => false])
        ->singleLineTextField('company', ['label' => 'Company'])
        ->create();
    $submission = formie()->submission($form)->with([
        'person' => ['firstName' => 'Jane', 'lastName' => 'Smith'],
        'address' => ['address1' => '123 Main St', 'city' => '', 'zip' => '0', 'country' => 'CA', 'state' => 'ON'],
        'single' => 'Alex Chen',
        'company' => 'Example',
    ])->save();
    $values = [
        'person' => 'Jane Smith', 'person.firstName' => 'Jane', 'person.lastName' => 'Smith',
        'address' => '123 Main St, 0, Canada, ON', 'address.address1' => '123 Main St',
        'address.city' => '', 'address.zip' => '0', 'address.country' => 'Canada', 'address.state' => 'ON',
        'single' => 'Alex Chen', 'company' => 'Example',
    ];
    $handles = match ($mode) {
        'combined' => ['person', 'address', 'single', 'company'],
        'subfields' => ['person.firstName', 'person.lastName', 'address.address1', 'address.city', 'address.zip', 'address.country', 'address.state', 'single', 'company'],
        default => array_keys($values),
    };
    $service = Formie::$plugin->getReportColumns();
    $admin = new User(['admin' => true]);
    $catalog = array_column($service->getFieldColumnsForFormIds([$form->id], $admin), null, 'handle');
    $report = new Report(['name' => 'Automatic subfields', 'handle' => 'automaticParts' . uniqid()]);
    Formie::$plugin->getReportEditor()->applyPayload($report, [
        'filters' => ['formIds' => [$form->id]],
        'display' => ['fieldColumnsMode' => 'all', 'compoundFieldColumns' => $mode, 'fieldOrder' => $order],
        'columns' => [
            ['type' => 'attribute', 'handle' => 'id', 'enabled' => true],
            ...array_map(fn($handle) => [...$catalog[$handle], 'enabled' => true], array_reverse($handles)),
        ],
    ]);
    expect(Formie::$plugin->getReports()->saveReport($report))->toBeTrue();
    $report = Formie::$plugin->getReports()->getReportById($report->id);
    expect($report->getSettingsModel()->display['compoundFieldColumns'])->toBe($mode);
    if ($order === 'custom') {
        $handles = array_reverse($handles);
    } elseif ($order === 'alphabetical') {
        usort($handles, fn($a, $b) => strcmp(strtolower($catalog[$a]['label']), strtolower($catalog[$b]['label'])));
    }
    $expectedHandles = ['id', ...$handles];
    $expectedHeaders = ['ID', ...array_map(fn($handle) => $catalog[$handle]['label'], $handles)];
    $expectedValues = [(string)$submission->id, ...array_map(fn($handle) => $values[$handle], $handles)];

    WebRequestTestHelper::withWebRequestContext(function () use ($report, $expectedHandles, $expectedHeaders, $expectedValues): void {
        $admin = User::find()->admin(true)->one();
        Craft::$app->getUser()->setIdentity($admin);
        $config = Formie::$plugin->getReportViewer()->getViewConfig($report, $admin);
        expect(array_column($config['columns'], 'handle'))->toBe($expectedHandles);
        $table = Formie::$plugin->getReportQuery()->getTableData($report, user: $admin, columnOverride: $config['viewerColumns']);
        expect(array_column($table['columns'], 'handle'))->toBe($expectedHandles);
        foreach ([null, $config['exportColumns']] as $override) {
            $export = Formie::$plugin->getReportExport()->export($report, 'csv', columnOverride: $override);
            try {
                $stream = fopen($export['path'], 'rb');
                fread($stream, 3);
                expect(fgetcsv($stream, null, ',', '"', ''))->toBe($expectedHeaders)
                    ->and(fgetcsv($stream, null, ',', '"', ''))->toBe($expectedValues);
                fclose($stream);
            } finally {
                unlink($export['path']);
            }
        }
    });
})->with(['combined', 'subfields', 'both'])->with(['layout', 'alphabetical', 'custom']);

it('keeps Combined as the default and ignores the automatic mode for manual selections', function (): void {
    $service = Formie::$plugin->getReportColumns();
    expect(ReportSettings::defaultDisplay()['compoundFieldColumns'])->toBe('combined');
    foreach ([null, '', 'invalid', ['subfields']] as $value) {
        $report = new Report();
        Formie::$plugin->getReportEditor()->applyPayload($report, ['display' => ['compoundFieldColumns' => $value]]);
        expect($report->getSettingsModel()->display['compoundFieldColumns'])->toBe('combined');
    }
    $form = formie()->form()->nameField('person', ['useMultipleFields' => true, 'rows' => (new Name())->getSubFields()])->create();
    foreach (['combined', 'subfields', 'both'] as $mode) {
        $report = new Report();
        $report->setSettingsModel(ReportSettings::fromArray([
            'filters' => ['formIds' => [$form->id]],
            'display' => ['fieldColumnsMode' => 'selected', 'compoundFieldColumns' => $mode],
            'columns' => array_map(fn($handle) => ['type' => 'field', 'handle' => $handle, 'enabled' => true], ['person.lastName', 'person']),
        ]));
        expect(array_column($service->resolveColumns($report, user: new User(['admin' => true])), 'handle'))->toBe(['person.lastName', 'person']);
    }
});

it('refreshes enabled automatic subfields without saving the report again', function (string $order): void {
    $form = formie()->form()->nameField('person', ['useMultipleFields' => true, 'rows' => (new Name())->getSubFields()])->create();
    $report = new Report();
    $report->setSettingsModel(ReportSettings::fromArray([
        'filters' => ['formIds' => [$form->id]],
        'display' => ['fieldColumnsMode' => 'all', 'compoundFieldColumns' => 'subfields', 'fieldOrder' => $order],
        'columns' => [
            ['type' => 'attribute', 'handle' => 'id', 'enabled' => false],
            ['type' => 'field', 'handle' => 'person.lastName', 'enabled' => true],
            ['type' => 'field', 'handle' => 'person.firstName', 'enabled' => true],
        ],
    ]));
    $name = $form->getFieldByHandle('person');
    foreach ($name->getFields() as $child) {
        if ($child->handle === 'middleName') {
            $child->enabled = true;
        }
        if ($child->handle === 'firstName') {
            $child->enabled = false;
        }
    }
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
    $columns = Formie::$plugin->getReportColumns()->resolveColumns($report, user: new User(['admin' => true]));
    expect(array_column($columns, 'handle'))->toBe($order === 'layout' ? ['person.middleName', 'person.lastName'] : ['person.lastName', 'person.middleName']);
})->with(['layout', 'alphabetical', 'custom']);

class ReportableDetailsField extends \verbb\formie\fields\Group implements \verbb\formie\base\ReportSubfieldsInterface
{
    public function getReportSubfields(): array
    {
        // A custom type can expose only the children appropriate for reporting.
        return array_values(array_filter($this->getFields(), fn($child) => $child->handle !== 'internal'));
    }
}

it('lets a custom compound field provide report subcolumns without type-specific report logic', function (string $mode): void {
    $fields = Formie::$plugin->getFields();
    $handler = function ($event): void { $event->fields[] = ReportableDetailsField::class; };
    \yii\base\Event::on(\verbb\formie\services\Fields::class, \verbb\formie\services\Fields::EVENT_REGISTER_FIELDS, $handler);
    $fields->resetFieldRegistryCache();
    try {
        $form = formie()->form()->addField(ReportableDetailsField::class, 'details', [
            'label' => 'Details',
            'rows' => [['fields' => [
                ['type' => \verbb\formie\fields\SingleLineText::class, 'handle' => 'code', 'label' => 'Code'],
                ['type' => \verbb\formie\fields\Dropdown::class, 'handle' => 'choice', 'label' => 'Choice', 'options' => [['label' => 'Design', 'value' => 'design']]],
                ['type' => \verbb\formie\fields\SingleLineText::class, 'handle' => 'internal', 'label' => 'Internal'],
                ['type' => \verbb\formie\fields\SingleLineText::class, 'handle' => 'disabled', 'label' => 'Disabled', 'enabled' => false],
                ['type' => \verbb\formie\fields\Heading::class, 'handle' => 'heading', 'label' => 'Heading'],
            ]]],
        ])->create();
        $submission = formie()->submission($form)->with(['details' => ['code' => '0', 'choice' => 'design', 'internal' => 'Private']])->save();
        $service = Formie::$plugin->getReportColumns();
        $admin = new User(['admin' => true]);
        $catalog = $service->getFieldColumnsForFormIds([$form->id], $admin);
        expect(array_column($catalog, 'handle'))->toBe(['details', 'details.code', 'details.choice'])
            ->and(array_column($catalog, 'label'))->toBe(['Details', 'Details: Code', 'Details: Choice']);
        $report = new Report();
        $report->setSettingsModel(ReportSettings::fromArray([
            'filters' => ['formIds' => [$form->id]],
            'display' => ['fieldColumnsMode' => 'all', 'compoundFieldColumns' => $mode],
            'columns' => [['type' => 'attribute', 'handle' => 'id', 'enabled' => false]],
        ]));
        $columns = $service->resolveColumns($report, user: $admin);
        $expected = match ($mode) {
            'combined' => ['details'],
            'subfields' => ['details.code', 'details.choice'],
            default => ['details', 'details.code', 'details.choice'],
        };
        expect(array_column($columns, 'handle'))->toBe($expected);
        $cells = $service->formatRow($submission, $columns, $report->getSettingsModel()->display);
        if ($mode !== 'combined') {
            expect(array_slice($cells, -2))->toBe(['0', 'Design']);
            expect(array_slice($service->formatRow($submission, $columns, ['useOptionLabels' => false]), -2))->toBe(['0', 'design']);
        }
        // Stored or forged columns cannot bypass the field's reporting declaration.
        $excluded = array_map(fn($handle) => ['type' => 'field', 'handle' => $handle], ['details.internal', 'details.disabled', 'details.heading']);
        expect($service->formatRow($submission, $excluded, []))->toBe([null, null, null]);
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        $query = Formie::$plugin->getReportQuery()->buildSubmissionQuery($report, $admin);
        $export = Formie::$plugin->getReportExport()->export($report, 'csv', $query);
        try {
            $stream = fopen($export['path'], 'rb');
            fread($stream, 3);
            expect(fgetcsv($stream, null, ',', '"', ''))->toBe(array_column($columns, 'header'))
                ->and(fgetcsv($stream, null, ',', '"', ''))->toBe(array_map(strval(...), $cells));
            fclose($stream);
        } finally {
            unlink($export['path']);
        }
    } finally {
        \yii\base\Event::off(\verbb\formie\services\Fields::class, \verbb\formie\services\Fields::EVENT_REGISTER_FIELDS, $handler);
        $fields->resetFieldRegistryCache();
    }
})->with(['combined', 'subfields', 'both']);
