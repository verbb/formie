<?php

use verbb\formie\elements\Form;
use verbb\formie\fields\Address;
use verbb\formie\fields\Dropdown;
use verbb\formie\fields\Entries;
use verbb\formie\fields\Quiz;
use verbb\formie\fields\Recipients;
use verbb\formie\fields\Survey;
use verbb\formie\fields\subfields\AddressCountry;
use verbb\formie\fields\subfields\AddressState;
use verbb\formie\Formie;
use verbb\formie\helpers\FormSerializer;
use verbb\formie\models\BrowserModule;

it('persists searchable limits through builder saves and both browser manifests', function (string $class, ?int $limit): void {
    $form = formie()->form()->addField($class, 'choice', array_merge($class === Dropdown::class ? [] : ['displayType' => 'dropdown'], [
        'useSearchable' => true,
        'searchableOptionLimit' => $limit,
    ]))->create();

    for ($save = 0; $save < 2; $save++) {
        Formie::$plugin->getFields()->resetFieldRegistryCache();
        $form = Form::find()->id($form->id)->one();
        $field = $form->getFieldByHandle('choice');
        $settings = json_decode(json_encode($field->getFormBuilderSettings(), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        expect($settings['searchableOptionLimit'])->toBe($limit);

        foreach ([BrowserModule::SURFACE_SERVER_RENDERED, BrowserModule::SURFACE_CLIENT_RENDERED] as $surface) {
            $modules = Formie::$plugin->getBrowserModuleManifestBuilder()->buildForSurface($form, $surface)->toArray()['entries'];
            $comboboxes = array_values(array_filter($modules, fn(array $module) => $module['moduleId'] === 'formie:combobox'));
            expect($comboboxes)->toHaveCount(1)
                ->and($comboboxes[0]['config']['maxOptions'])->toBe($limit);
        }

        $pages = json_decode(json_encode($form->getFormLayout()->getFormBuilderConfig(), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        $pages[0]['rows'][0]['fields'] = [$settings];
        $form->getFormLayout()->setPages((new FormSerializer())->hydrateBuilder($pages, $form));
        expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
    }
})->with([Dropdown::class, Entries::class, Recipients::class, Survey::class, Quiz::class])->with([25, null]);

it('defaults searchable controls to 100 and rejects invalid display limits', function (string $class): void {
    $field = new $class();
    expect($field->searchableOptionLimit)->toBe(100)
        ->and($field->settingsAttributes())->toContain('searchableOptionLimit');

    foreach ([1, 100, 500, null] as $limit) {
        $field->searchableOptionLimit = $limit;
        expect($field->validate(['searchableOptionLimit']))->toBeTrue();
    }

    foreach ([0, -1, 1.5] as $limit) {
        $field->searchableOptionLimit = $limit;
        expect($field->validate(['searchableOptionLimit']))->toBeFalse();
    }
})->with([Dropdown::class, Entries::class, Recipients::class, Survey::class, Quiz::class, AddressCountry::class, AddressState::class]);

it('preserves independent country and dynamic state limits in an address', function (): void {
    $rows = (new Address())->getSubFields();
    foreach ($rows as &$row) {
        foreach ($row['fields'] as &$field) {
            if ($field['handle'] === 'country') {
                $field['useSearchable'] = true;
                $field['searchableOptionLimit'] = null;
            } elseif ($field['handle'] === 'state') {
                $field['inputMode'] = 'dropdownWhenAvailable';
                $field['searchableOptionLimit'] = 20;
            }
        }
        unset($field);
    }
    unset($row);

    $form = formie()->form()->addressField('address', ['rows' => $rows])->create();
    Formie::$plugin->getFields()->resetFieldRegistryCache();
    $form = Form::find()->id($form->id)->one();
    $modules = Formie::$plugin->getBrowserModuleManifestBuilder()->buildForSurface($form, BrowserModule::SURFACE_SERVER_RENDERED)->toArray()['entries'];
    $country = array_values(array_filter($modules, fn(array $module) => $module['moduleId'] === 'formie:combobox'));
    $state = array_values(array_filter($modules, fn(array $module) => $module['moduleId'] === 'formie:address-state'));
    expect($country)->toHaveCount(1)
        ->and($country[0]['config']['maxOptions'])->toBeNull()
        ->and($state)->toHaveCount(1)
        ->and($state[0]['config']['searchableOptionLimit'])->toBe(20);
});


it('exposes the display limit in searchable field schemas and GraphQL settings', function (string $class): void {
    $field = new $class();
    $schema = $field->getFormBuilderSchema();
    $names = [];
    $visit = function (array $nodes) use (&$visit, &$names): void {
        foreach ($nodes as $key => $value) {
            if ($key === 'name' && is_string($value)) $names[] = $value;
            if (is_array($value)) $visit($value);
        }
    };
    $visit($schema);
    expect($names)->toContain('searchableOptionLimit')
        ->and($field->getSettingGqlTypes())->toHaveKey('searchableOptionLimit');
})->with([Dropdown::class, Entries::class, Recipients::class, AddressCountry::class, AddressState::class]);
