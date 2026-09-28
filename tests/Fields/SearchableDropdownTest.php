<?php

declare(strict_types=1);

use verbb\formie\compatibility\fields\FieldConfigNormalizer;
use verbb\formie\fields\Dropdown;
use verbb\formie\Formie;
use verbb\formie\models\BrowserModule;
use verbb\formie\theme\context\RenderContext;

it('includes combobox modules for searchable dropdown fields on the frontend', function (): void {
    $form = formie()
        ->form(['title' => 'Searchable Dropdown Modules'])
        ->dropdownField('country', [
            'useSearchable' => true,
            'options' => [
                ['label' => 'Australia', 'value' => 'au'],
                ['label' => 'New Zealand', 'value' => 'nz'],
            ],
        ])
        ->dropdownField('tags', [
            'useSearchable' => true,
            'multi' => true,
            'options' => [
                ['label' => 'One', 'value' => 'one'],
                ['label' => 'Two', 'value' => 'two'],
            ],
        ])
        ->create();

    $builder = Formie::$plugin->getBrowserModuleManifestBuilder();
    $frontendModules = $builder->buildForSurface($form, BrowserModule::SURFACE_SERVER_RENDERED)->toArray()['entries'];
    $frontendModuleIds = array_values(array_map(static fn(array $module): string => (string)$module['moduleId'], $frontendModules));
    $comboboxModules = array_values(array_filter($frontendModules, static fn(array $module): bool => ($module['moduleId'] ?? null) === 'formie:combobox'));

    expect($frontendModuleIds)->toContain('formie:combobox')
        ->and($comboboxModules)->toHaveCount(2);

    $multipleFlags = array_values(array_map(static fn(array $module): ?bool => $module['config']['multiple'] ?? null, $comboboxModules));

    expect($multipleFlags)->toContain(false)
        ->and($multipleFlags)->toContain(true);
});

it('does not include combobox modules when searchable dropdown is disabled', function (): void {
    $form = formie()
        ->form(['title' => 'Standard Dropdown Modules'])
        ->dropdownField('country', [
            'useSearchable' => false,
            'options' => [
                ['label' => 'Australia', 'value' => 'au'],
            ],
        ])
        ->create();

    $builder = Formie::$plugin->getBrowserModuleManifestBuilder();
    $frontendModuleIds = array_values(array_map(
        static fn(array $module): string => (string)$module['moduleId'],
        $builder->buildForSurface($form, BrowserModule::SURFACE_SERVER_RENDERED)->toArray()['entries'],
    ));

    expect($frontendModuleIds)->not->toContain('formie:combobox');
});

it('renders combobox data attributes when searchable dropdown is enabled', function (): void {
    $form = formie()
        ->form(['title' => 'Searchable Dropdown Render'])
        ->dropdownField('country', [
            'useSearchable' => true,
            'options' => [
                ['label' => 'Australia', 'value' => 'au'],
                ['label' => 'New Zealand', 'value' => 'nz'],
            ],
        ])
        ->create();

    /** @var Dropdown $field */
    $field = $form->getFieldByHandle('country');

    $tag = $field->renderSlotTag('fieldInput', RenderContext::from([
        'form' => $form,
        'value' => '',
    ]));

    expect($tag?->coreAttributes['data']['formie-combobox-input'] ?? null)->toBeTrue()
        ->and($tag?->coreAttributes['data']['formie-dropdown-input'] ?? null)->toBeTrue();
});

it('preserves useSearchable when normalizing dropdown field config', function (): void {
    $config = [
        'useSearchable' => true,
        'options' => [
            ['label' => 'One', 'value' => 'one'],
        ],
    ];

    FieldConfigNormalizer::normalize($config, Dropdown::class);

    expect($config['useSearchable'] ?? null)->toBeTrue();
});
