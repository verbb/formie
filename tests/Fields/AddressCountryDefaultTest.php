<?php

declare(strict_types=1);

use craft\i18n\Locale;
use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Address;
use verbb\formie\fields\subfields\AddressCountry;
use verbb\formie\helpers\FormSerializer;

it('renders country defaults in the configured value format through builder saves', function (string $language, string $default, string $format, string $expected): void {
    $languageBefore = Craft::$app->language;
    $localeBefore = Craft::$app->getLocale();
    Craft::$app->language = $language;
    Craft::$app->set('locale', new Locale($language));

    try {
        $rows = (new Address())->getSubFields();

        foreach ($rows as &$row) {
            foreach ($row['fields'] as &$child) {
                if ($child['handle'] === 'country') {
                    $child['defaultValue'] = $default;
                    $child['optionValue'] = $format;
                }
            }
            unset($child);
        }
        unset($row);

        $form = formie()->form()->addressField('address', ['rows' => $rows])->create();

        for ($save = 0; $save < 2; $save++) {
            Formie::$plugin->getFields()->resetFieldRegistryCache();
            $form = Form::find()->id($form->id)->one();
            $country = $form->getFieldByHandle('address')->getFieldByHandle('country');

            expect($country->getDefaultValue()->value)->toBe($expected)
                ->and($country->getDefaultValue()->valid)->toBeTrue();

            $html = (string)Formie::$plugin->getRendering()->renderField($form, 'address');
            $document = new DOMDocument();
            @$document->loadHTML('<?xml encoding="UTF-8">' . $html);
            $selected = (new DOMXPath($document))->query('//select[@data-formie-address-country-input]/option[@selected]');

            expect($selected->length)->toBe(1)
                ->and($selected->item(0)->getAttribute('value'))->toBe($expected);

            // Defaults use ISO identities in the editor so format changes cannot orphan them.
            $settings = $country->getFormBuilderSettings();
            expect($settings['defaultValue'])->toBe('NL')
                ->and(array_column($settings['options'], 'value'))->toContain('NL');

            $pages = json_decode(json_encode($form->getFormLayout()->getFormBuilderConfig(), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
            $form->getFormLayout()->setPages((new FormSerializer())->hydrateBuilder($pages, $form));
            expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
        }
    } finally {
        Craft::$app->language = $languageBefore;
        Craft::$app->set('locale', $localeBefore);
    }
})->with([
    'Dutch legacy ISO to full name' => ['nl', 'NL', 'full', 'Nederland'],
    'Dutch full name' => ['nl', 'Nederland', 'full', 'Nederland'],
    'German legacy ISO to full name' => ['de', 'NL', 'full', 'Niederlande'],
    'English ISO' => ['en', 'NL', 'short', 'NL'],
    'full name to ISO' => ['en', 'Netherlands', 'short', 'NL'],
]);

it('keeps country default choices independent of the submitted value format', function (): void {
    $country = new AddressCountry(['optionLabel' => 'short', 'optionValue' => 'full']);
    $schema = $country->defineFormBuilderGeneralSchema();
    $default = array_values(array_filter($schema, fn(array $node): bool => ($node['name'] ?? '') === 'defaultValue'))[0];

    expect(array_column($default['options'], 'value'))->toContain('NL')
        ->and(array_column($country->options(), 'value'))->toContain('Netherlands');
});

it('does not change submitted countries or replace cleared defaults', function (): void {
    $country = new AddressCountry(['defaultValue' => 'NL', 'optionValue' => 'full']);
    $submission = new Submission();

    expect($country->normalizeValue('Germany', $submission)->value)->toBe('Germany')
        ->and($country->normalizeValue('', $submission))->toBeNull();

    foreach ([null, ''] as $blank) {
        $country->defaultValue = $blank;
        expect($country->getDefaultValue())->toBeNull();
    }
});

it('retains the default country when the editor changes the option format', function (): void {
    $country = new AddressCountry(['defaultValue' => 'Netherlands', 'optionValue' => 'full']);
    $settings = $country->getFormBuilderSettings();

    $settings['optionValue'] = 'short';
    $country = Formie::$plugin->getFields()->createField($settings);
    expect($country->getDefaultValue()->value)->toBe('NL');

    $settings = $country->getFormBuilderSettings();
    $settings['optionValue'] = 'full';
    $country = Formie::$plugin->getFields()->createField($settings);
    expect($country->getDefaultValue()->value)->toBe('Netherlands');
});

it('does not resolve unknown or excluded countries into defaults', function (): void {
    $rows = (new Address())->getSubFields();

    foreach ($rows as &$row) {
        foreach ($row['fields'] as &$child) {
            if ($child['handle'] === 'country') {
                $child['defaultValue'] = 'NL';
                $child['optionValue'] = 'full';
            }
        }
        unset($child);
    }
    unset($row);

    $form = formie()->form()->addressField('address', ['rows' => $rows, 'allowedCountryCodes' => ['DE']])->create();
    $country = $form->getFieldByHandle('address')->getFieldByHandle('country');

    expect($country->getDefaultValue()->valid)->toBeFalse()
        ->and(array_column($country->getFormBuilderSettings()['options'], 'value'))->toBe(['DE']);

    $country->defaultValue = 'Not a country';
    expect($country->getDefaultValue()->valid)->toBeFalse();
});
