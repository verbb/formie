<?php

declare(strict_types=1);

use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\subfields\AddressAutoComplete;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m231125_000000_craft5;

function legacyFieldConversion(string $method, array $settings): array
{
    $migration = new m231125_000000_craft5();

    return (new ReflectionMethod($migration, $method))->invoke($migration, $settings);
}

function legacyDateEnabledParts(array $rows): array
{
    $parts = [];

    foreach ($rows as $row) {
        foreach ($row['fields'] as $field) {
            if ($field['enabled'] ?? true) {
                $parts[] = $field['handle'];
            }
        }
    }

    return $parts;
}

function legacyDateControlNames(Form $form, string $handle): array
{
    $document = new DOMDocument();
    @$document->loadHTML('<?xml encoding="UTF-8">' . Formie::$plugin->getRendering()->renderField($form, $form->getFieldByHandle($handle)));
    $names = [];

    foreach ((new DOMXPath($document))->query('//input[@name]|//select[@name]') as $node) {
        if (preg_match('/\[([A-Za-z]+)\]$/', $node->getAttribute('name'), $match)) {
            $names[] = $match[1];
        }
    }

    return $names;
}

dataset('legacy date displays', [
    'dropdowns' => ['dropdowns', '_getDateDropdownsConfig'],
    'inputs' => ['inputs', '_getDateInputsConfig'],
]);

it('enables the date parts each Formie 2 format showed', function(string $display, string $method, array $settings, array $expected): void {
    expect(legacyDateEnabledParts(legacyFieldConversion($method, ['displayType' => $display, ...$settings])))->toBe($expected);
})->with('legacy date displays')->with([
    'hours and minutes' => [['includeDate' => false, 'includeTime' => true, 'timeFormat' => 'H:i'], ['hour', 'minute']],
    'with seconds' => [['includeDate' => false, 'includeTime' => true, 'timeFormat' => 'H:i:s'], ['hour', 'minute', 'second']],
    'twelve hour' => [['includeDate' => false, 'includeTime' => true, 'timeFormat' => 'h:i A'], ['hour', 'minute', 'ampm']],
    'twelve hour with seconds' => [['includeDate' => false, 'includeTime' => true, 'timeFormat' => 'h:i:s A'], ['hour', 'minute', 'second', 'ampm']],
    'minutes and seconds' => [['includeDate' => false, 'includeTime' => true, 'timeFormat' => 'i:s'], ['minute', 'second']],
    'date and time' => [['includeDate' => true, 'includeTime' => true, 'dateFormat' => 'd-m-Y', 'timeFormat' => 'i:s'], ['year', 'month', 'day', 'minute', 'second']],
    'date only ignores the time format' => [['includeDate' => true, 'includeTime' => false, 'dateFormat' => 'd-m-Y', 'timeFormat' => 'h:i:s A'], ['year', 'month', 'day']],
    'unset settings use the Formie 2 defaults' => [[], ['year', 'month', 'day', 'hour', 'minute']],
]);

it('limits the hour part to what a twelve hour format offered', function(): void {
    $dropdown = fn(string $format) => array_column(array_values(array_filter(legacyFieldConversion('_getDateDropdownsConfig', ['timeFormat' => $format])[0]['fields'], fn($field) => $field['handle'] === 'hour'))[0]['options'], 'value');
    $input = fn(string $format) => array_values(array_filter(legacyFieldConversion('_getDateInputsConfig', ['timeFormat' => $format])[0]['fields'], fn($field) => $field['handle'] === 'hour'))[0]['max'];

    expect((string)max($dropdown('H:i')))->toBe('23')
        ->and((string)max($dropdown('h:i A')))->toBe('12')
        ->and((string)max($dropdown('h:i:s A')))->toBe('12')
        ->and((string)max($dropdown('i:s')))->toBe('23')
        ->and($input('H:i:s'))->toBe(23)
        ->and($input('h:i A'))->toBe(12);
});

it('renders and stores a converted twelve hour date with its seconds', function(string $display, string $method): void {
    $form = formie()->form()->dateField('when', [
        'displayType' => $display,
        'dateFormat' => 'd-m-Y',
        'timeFormat' => 'h:i:s A',
        'rows' => legacyFieldConversion($method, ['dateFormat' => 'd-m-Y', 'timeFormat' => 'h:i:s A']),
    ])->create();
    $form = Form::find()->id($form->id)->one();

    expect(legacyDateControlNames($form, 'when'))->toBe(['year', 'month', 'day', 'hour', 'minute', 'second', 'ampm']);

    $submission = formie()->submission($form)->with(['when' => ['year' => '2026', 'month' => '11', 'day' => '4', 'hour' => '3', 'minute' => '5', 'second' => '9', 'ampm' => 'PM']])->save();
    $reloaded = Submission::find()->id($submission->id)->status(null)->one();
    $field = $reloaded->getForm()->getFieldByHandle('when');

    expect($field->getValueAsString($reloaded->getFieldValue('when'), $reloaded))->toBe('04-11-2026 03:05:09 PM');
})->with('legacy date displays');

it('renders and stores a converted minutes and seconds date without an hour', function(string $display, string $method): void {
    $form = formie()->form()->dateField('elapsed', [
        'displayType' => $display,
        'timeFormat' => 'i:s',
        'rows' => legacyFieldConversion($method, ['includeDate' => false, 'timeFormat' => 'i:s']),
    ])->create();
    $form = Form::find()->id($form->id)->one();

    expect(legacyDateControlNames($form, 'elapsed'))->toBe(['minute', 'second']);

    $submission = formie()->submission($form)->with(['elapsed' => ['minute' => '5', 'second' => '9']])->save();
    $reloaded = Submission::find()->id($submission->id)->status(null)->one();
    $parts = $reloaded->getFieldValue('elapsed')->toArray();

    expect($parts['minute'] ?? null)->toBe('5')
        ->and($parts['second'] ?? null)->toBe('9')
        ->and($parts['hour'] ?? null)->toBeNull();
})->with('legacy date displays');

it('converts an enabled Formie 2 auto-complete setting into a sub-field', function(): void {
    $rows = legacyFieldConversion('_getAddressConfig', [
        'autocompleteEnabled' => true,
        'autocompleteIntegration' => 'googleMaps',
        'autocompleteLabel' => 'Find address',
        'autocompletePlaceholder' => 'Start typing',
        'autocompleteRequired' => true,
        'autocompleteCurrentLocation' => true,
    ]);
    $child = $rows[0]['fields'][0];

    expect($child['type'])->toBe(AddressAutoComplete::class)
        ->and($child['handle'])->toBe('autoComplete')
        ->and($child['enabled'])->toBeTrue()
        ->and($child['integrationHandle'])->toBe('googleMaps')
        ->and($child['label'])->toBe('Find address')
        ->and($child['placeholder'])->toBe('Start typing')
        ->and($child['required'])->toBeTrue()
        ->and($child['currentLocation'])->toBeTrue();

    $form = formie()->form()->addressField('address', ['rows' => $rows])->create();
    $form = Form::find()->id($form->id)->one();
    $saved = $form->getFieldByHandle('address')->getFieldByHandle('autoComplete');

    expect($saved)->toBeInstanceOf(AddressAutoComplete::class)
        ->and($saved->integrationHandle)->toBe('googleMaps')
        ->and($saved->getIsDisabled())->toBeFalse();

    $submission = formie()->submission($form)->save();
    Db::update(Table::FORMIE_SUBMISSIONS, ['content' => [$form->getFieldByHandle('address')->uid => ['autocomplete' => '1 Legacy Way', 'address1' => null]]], ['id' => $submission->id]);
    $reloaded = Submission::find()->id($submission->id)->status(null)->one();

    expect($reloaded->getFieldValue('address.autoComplete'))->toBe('1 Legacy Way')
        ->and(Json::decode((new Query())->select('content')->from(Table::FORMIE_SUBMISSIONS)->where(['id' => $submission->id])->scalar()))->toHaveKey($form->getFieldByHandle('address')->uid);
});

it('adds no auto-complete sub-field when the Formie 2 setting was off', function(array $settings): void {
    $handles = [];

    foreach (legacyFieldConversion('_getAddressConfig', $settings) as $row) {
        array_push($handles, ...array_column($row['fields'], 'handle'));
    }

    expect($handles)->toBe(['address1', 'address2', 'address3', 'city', 'zip', 'state', 'country']);
})->with([
    'off' => [['autocompleteEnabled' => false, 'autocompleteIntegration' => 'googleMaps']],
    'unset' => [[]],
]);
