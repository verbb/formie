<?php

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\fields\Address;
use verbb\formie\fields\Phone;
use verbb\formie\fields\subfields\AddressAutoComplete;
use verbb\formie\fields\subfields\AddressCountry;
use verbb\formie\helpers\FormSerializer;
use verbb\formie\integrations\addressproviders\Google;

it('offers an empty country default without adding it to allowed countries', function(string $type, string $setting) {
    $field = new $type();
    $schema = $field->defineFormBuilderGeneralSchema();
    $control = array_values(array_filter($schema, fn($node) => ($node['name'] ?? null) === $setting))[0];
    expect(array_column($control['options'], 'value'))->toContain('')->toContain('AU');
    $emptyOptions = array_values(array_filter($control['options'], fn($option) => $option['value'] === ''));
    expect($emptyOptions)->toHaveCount(1)->and($emptyOptions[0]['label'])->toBe('None');

    if ($field instanceof Phone) {
        expect(array_column($field->getCountryOptions(), 'value'))->not->toContain('');
    }
})->with([
    [Phone::class, 'countryDefaultValue'],
    [AddressCountry::class, 'defaultValue'],
    [AddressAutoComplete::class, 'countryDefaultValue'],
]);

it('retains cleared country defaults through repeated builder saves', function(?string $empty) {
    $provider = new Google(['name' => 'Country clearing', 'handle' => 'countryClearing', 'enabled' => true, 'apiKey' => 'test-only']);
    $integrations = Formie::$plugin->getIntegrations();
    expect($integrations->saveIntegration($provider, false))->toBeTrue();

    try {
        $rows = (new Address())->getSubFields();
        foreach ($rows as &$row) {
            foreach ($row['fields'] as &$child) {
                if ($child['handle'] === 'country') {
                    $child['defaultValue'] = 'AU';
                }
                if ($child['handle'] === 'autoComplete') {
                    $child['countryDefaultValue'] = 'AU';
                    $child['enabled'] = true;
                }
            }
            unset($child);
        }
        unset($row);
        $form = formie()->form()->phoneField('phone', ['countryDefaultValue' => 'AU'])
            ->addressField('address', ['rows' => $rows])->create();

        for ($save = 0; $save < 2; $save++) {
            $pages = json_decode(json_encode($form->getFormLayout()->getFormBuilderConfig()), true);
            $clear = function(array &$node) use (&$clear, $empty) {
                if (($node['type'] ?? null) === Phone::class || ($node['type'] ?? null) === AddressAutoComplete::class) {
                    $node['countryDefaultValue'] = $empty;
                }
                if (($node['type'] ?? null) === AddressCountry::class) {
                    $node['defaultValue'] = $empty;
                }
                foreach ($node as &$value) {
                    if (is_array($value)) {
                        $clear($value);
                    }
                }
                unset($value);
            };
            if ($save === 0) {
                $clear($pages);
            }
            $form->getFormLayout()->setPages((new FormSerializer())->hydrateBuilder($pages, $form));
            expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
            Formie::$plugin->getFields()->resetFieldRegistryCache();
            $form = Form::find()->id($form->id)->one();
            $address = $form->getFieldByHandle('address');
            expect($form->getFieldByHandle('phone')->countryDefaultValue)->toBeIn(['', null])
                ->and($address->getFieldByHandle('country')->getFormBuilderSettings()['defaultValue'])->toBeIn(['', null])
                ->and($address->getFieldByHandle('autoComplete')->countryDefaultValue)->toBeIn(['', null]);
        }
    } finally {
        // Address defaults depend on globally enabled providers; do not leak this fixture into later tests.
        $provider->enabled = false;
        expect($integrations->saveIntegration($provider, false))->toBeTrue();
        expect($integrations->deleteIntegration($provider))->toBeTrue();
    }
})->with(['empty string' => [''], 'null' => [null]]);
