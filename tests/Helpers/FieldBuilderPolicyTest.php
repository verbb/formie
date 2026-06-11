<?php

declare(strict_types=1);

use verbb\formie\fields\Dropdown;
use verbb\formie\fields\Entries;
use verbb\formie\fields\Phone;
use verbb\formie\Formie;
use verbb\formie\helpers\FieldBuilderPolicy;

it('forces multi off when multi-select dropdowns are disabled', function (): void {
    Formie::$plugin->getSettings()->allowMultiSelectDropdowns = false;

    $dropdown = new Dropdown(['multi' => true]);
    $entries = new Entries(['multi' => true]);

    expect($dropdown->multi)->toBeFalse()
        ->and($entries->multi)->toBeFalse();
});

it('forces phone country selector off when disabled', function (): void {
    Formie::$plugin->getSettings()->allowPhoneCountrySelector = false;

    $phone = new Phone(['countryEnabled' => true]);

    expect($phone->countryEnabled)->toBeFalse();
});

it('omits multi-select schema when multi-select dropdowns are disabled', function (): void {
    Formie::$plugin->getSettings()->allowMultiSelectDropdowns = false;

    expect(FieldBuilderPolicy::multiSelectDropdownSchema())->toBe([]);
});

it('omits phone country selector schema when disabled', function (): void {
    Formie::$plugin->getSettings()->allowPhoneCountrySelector = false;

    expect(FieldBuilderPolicy::phoneCountrySelectorSchema())->toBe([]);
});

it('includes multi-select schema when multi-select dropdowns are allowed', function (): void {
    Formie::$plugin->getSettings()->allowMultiSelectDropdowns = true;

    $schema = FieldBuilderPolicy::multiSelectDropdownSchema([
        'if' => 'displayType == "dropdown"',
    ]);

    expect($schema)->toHaveCount(1)
        ->and($schema[0]['name'] ?? null)->toBe('multi')
        ->and($schema[0]['if'] ?? null)->toBe('displayType == "dropdown"');
});

it('hides dropdown multi setting in form builder schema when disabled', function (): void {
    Formie::$plugin->getSettings()->allowMultiSelectDropdowns = false;

    $dropdown = new Dropdown();
    $schema = $dropdown->defineFormBuilderGeneralSchema();
    $names = array_values(array_filter(array_map(
        static fn(array $node): ?string => $node['name'] ?? null,
        $schema,
    )));

    expect($names)->not->toContain('multi');
});

it('hides phone country selector in form builder schema when disabled', function (): void {
    Formie::$plugin->getSettings()->allowPhoneCountrySelector = false;

    $phone = new Phone();
    $schema = $phone->defineFormBuilderGeneralSchema();
    $names = array_values(array_filter(array_map(
        static fn(array $node): ?string => $node['name'] ?? null,
        $schema,
    )));

    expect($names)->not->toContain('countryEnabled');
});
