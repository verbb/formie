<?php

use craft\base\ElementInterface;
use craft\db\Query;
use craft\elements\User;
use craft\helpers\Json;
use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\content\ContentEncryption;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Address;
use verbb\formie\fields\Date;
use verbb\formie\fields\Dropdown;
use verbb\formie\fields\Name;
use verbb\formie\fields\Password;
use verbb\formie\fields\Payment as PaymentField;
use verbb\formie\fields\Recipients;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\Table;
use verbb\formie\models\Payment;
use verbb\formie\models\Report;
use verbb\formie\models\ReportSettings;

it('gives fields an independent report override and modification event', function (): void {
    $field = new class extends SingleLineText {
        protected function defineValueForReport(mixed $value, ?ElementInterface $element = null, bool $useOptionLabels = true): mixed
        {
            return 'Report: ' . $this->defineValueAsString($value, $element) . ($useOptionLabels ? ' labels' : ' values');
        }
    };
    $calls = [];
    foreach ([Field::EVENT_MODIFY_VALUE_AS_STRING, Field::EVENT_MODIFY_VALUE_FOR_EXPORT, Field::EVENT_MODIFY_VALUE_FOR_REPORT] as $eventName) {
        $field->on($eventName, function ($event) use (&$calls, $eventName): void {
            $calls[] = $eventName;
            $event->value .= '!';
        });
    }
    expect($field->getValueForReport('Jane', useOptionLabels: false))->toBe('Report: Jane values!')
        ->and($calls)->toBe([Field::EVENT_MODIFY_VALUE_FOR_REPORT]);
    expect((new SingleLineText())->getValueForReport('0'))->toBe('0')
        ->and((new SingleLineText())->getValueForReport(null))->toBe('');
});

it('uses readable combined report cells while retaining structured export projections', function (bool $useLabels): void {
    $form = formie()->form()
        ->nameField('person', ['useMultipleFields' => true, 'rows' => (new Name())->getSubFields()])
        ->addressField('address', ['rows' => (new Address())->getSubFields()])
        ->create();
    $submission = formie()->submission($form)->with([
        'person' => ['firstName' => 'Jane', 'lastName' => 'Smith'],
        'address' => ['address1' => '123 Main St', 'city' => 'Toronto', 'zip' => 'M5V 1A1', 'state' => 'ON', 'country' => 'CA'],
    ])->save();
    $submission = Submission::find()->id($submission->id)->one();
    $expected = ['Jane Smith', '123 Main St, Toronto, M5V 1A1, ' . ($useLabels ? 'Canada' : 'CA') . ', ON'];
    $columns = [
        ['type' => 'field', 'handle' => 'person', 'label' => 'Name', 'enabled' => true],
        ['type' => 'field', 'handle' => 'address', 'label' => 'Address', 'enabled' => true],
    ];
    $report = new Report(['name' => 'Readable cells', 'handle' => 'readableCells' . uniqid()]);
    $report->setSettingsModel(ReportSettings::fromArray([
        'filters' => ['formIds' => [$form->id]],
        'display' => ['fieldColumnsMode' => 'selected', 'useOptionLabels' => $useLabels],
        'columns' => $columns,
    ]));
    $service = Formie::$plugin->getReportColumns();
    $resolved = $service->resolveColumns($report);
    expect($service->formatRow($submission, $resolved, $report->getSettingsModel()->display))->toBe($expected)
        ->and(array_column($service->formatViewerRow($submission, $resolved, $report->getSettingsModel()->display), 'value'))->toBe($expected)
        ->and($submission->getFieldValueForExport('person'))->toBeArray()
        ->and($submission->getFieldValueForExport('address'))->toBeArray();
    $query = Formie::$plugin->getReportQuery()->buildSubmissionQuery($report, new User(['admin' => true]));
    $export = Formie::$plugin->getReportExport()->export($report, 'csv', $query);
    try {
        $stream = fopen($export['path'], 'rb');
        try {
            fread($stream, 3);
            expect(fgetcsv($stream, null, ',', '"', ''))->toBe(['Name', 'Address'])
                ->and(fgetcsv($stream, null, ',', '"', ''))->toBe($expected);
        } finally {
            fclose($stream);
        }
    } finally {
        unlink($export['path']);
    }
})->with([true, false]);

it('uses the formatted date range as a report cell', function (): void {
    $field = new Date(['handle' => 'dates', 'collectMode' => Date::COLLECT_RANGE, 'displayType' => 'datePicker', 'dateFormat' => 'Y-m-d']);
    $value = $field->normalizeFieldValue(['startDate' => '2026-06-01', 'endDate' => '2026-06-10']);
    expect($field->getValueForReport($value))->toBe('2026-06-01 – 2026-06-10')
        ->and($field->getValueForExport($value))->toBeArray();
});

it('uses recipient labels or values in report cells', function (): void {
    $field = new Recipients(['displayType' => 'dropdown', 'options' => [['label' => 'Sales', 'value' => 'sales@example.test']]]);
    $value = $field->normalizeFieldValue('sales@example.test');
    expect($field->getValueForReport($value))->toBe('Sales')
        ->and($field->getValueForReport($value, useOptionLabels: false))->toBe('sales@example.test');
});

it('honors prefix label preferences without mutating the full name', function (): void {
    $rows = (new Name())->getSubFields();
    $rows[0]['fields'][0]['enabled'] = true;
    $form = formie()->form()->nameField('person', ['useMultipleFields' => true, 'rows' => $rows])->create();
    $submission = formie()->submission($form)->with(['person' => ['prefix' => 'dr', 'firstName' => 'Jane', 'lastName' => 'Smith']])->save();
    expect($submission->getFieldValueForReport('person'))->toBe('Dr. Jane Smith')
        ->and($submission->getFieldValueForReport('person', false))->toBe('dr Jane Smith')
        ->and($submission->getFieldValueForReport('person.prefix', false))->toBe('dr')
        ->and($submission->getFieldValueForReport('person'))->toBe('Dr. Jane Smith');
});

it('uses recorded payment details rather than payment input in reports', function (): void {
    $field = new PaymentField(['id' => 123, 'handle' => 'payment', 'label' => 'Payment']);
    $submission = new class extends Submission {
        public function getPayments(): ?array
        {
            return [new Payment(['fieldId' => 123, 'amount' => '0', 'currency' => 'USD', 'status' => 'success', 'reference' => 'txn_123'])];
        }
    };
    $value = $field->normalizeFieldValue(['amount' => '99.99', 'currency' => 'EUR']);
    expect($field->getValueForReport($value, $submission))->toBe('Single, Succeeded, 0.00, USD, txn_123')
        ->and($field->getValueForReport($value))->toBe('');
});

it('preserves nested password masking option preferences and zero report values', function (string $placement, bool $useLabels): void {
    $form = formie()->form()->{$placement . 'Field'}('details', ['rows' => [['fields' => [
        ['type' => Password::class, 'handle' => 'secret', 'label' => 'Secret'],
        ['type' => Dropdown::class, 'handle' => 'choice', 'label' => 'Choice', 'options' => [['label' => 'Zero choice', 'value' => '0']]],
        ['type' => SingleLineText::class, 'handle' => 'zero', 'label' => 'Zero'],
        ['type' => SingleLineText::class, 'handle' => 'blank', 'label' => 'Blank'],
    ]]]])->create();
    $row = ['secret' => 'Secret123!', 'choice' => '0', 'zero' => '0', 'blank' => ''];
    $submission = formie()->submission($form)->with(['details' => $placement === 'group' ? $row : [$row, $row]])->save();
    $submission = Submission::find()->id($submission->id)->one();
    $expectedRow = '•••••••••••••••••••••, ' . ($useLabels ? 'Zero choice' : '0') . ', 0';
    $expected = $placement === 'group' ? $expectedRow : $expectedRow . ', ' . $expectedRow;
    expect($submission->getFieldValueForReport('details', $useLabels))->toBe($expected);
})->with(['group', 'repeater'])->with([true, false]);

it('keeps unavailable encrypted report cells readable without exposing locked values', function (bool $useLabels): void {
    $form = formie()->form()
        ->nameField('person', ['enableContentEncryption' => true, 'useMultipleFields' => true, 'rows' => (new Name())->getSubFields()])
        ->dropdownField('visible', ['options' => [['label' => 'Readable choice', 'value' => 'readable']]])
        ->create();
    $submission = formie()->submission($form)->with(['person' => ['firstName' => 'Secret'], 'visible' => 'readable'])->save();
    $content = Json::decode((new Query())->select('content')->from(Table::FORMIE_SUBMISSIONS)->where(['id' => $submission->id])->scalar());
    $key = ContentEncryption::generateKey();
    $content[$form->getFieldByHandle('person')->uid] = ['__formie_enc' => 2, 'k' => $key->id, 'c' => ContentEncryption::encryptV2(Json::encode(['firstName' => 'Secret']), $key)];
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_SUBMISSIONS, ['content' => $content], ['id' => $submission->id])->execute();
    $submission = Submission::find()->id($submission->id)->one();
    expect($submission->hasLockedContent())->toBeTrue();
    $service = Formie::$plugin->getReportColumns();
    foreach (['person', 'person.firstName'] as $handle) {
        expect($service->formatCell($submission, ['type' => 'field', 'handle' => $handle], ['useOptionLabels' => $useLabels]))->toBe('[encrypted: unavailable]');
    }
    expect($service->formatCell($submission, ['type' => 'field', 'handle' => 'visible'], ['useOptionLabels' => $useLabels]))->toBe($useLabels ? 'Readable choice' : 'readable');
})->with([true, false]);
