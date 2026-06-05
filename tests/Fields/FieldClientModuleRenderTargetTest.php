<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\models\ClientModule;

it('filters frontend-only field modules out of cp edit manifests and config', function (): void {
    $form = formie()
        ->form(['title' => 'Client Module Render Targets'])
        ->singleLineTextField('headline', [
            'limit' => true,
            'max' => 20,
            'maxType' => 'characters',
        ])
        ->multiLineTextField('bio', [
            'useRichText' => true,
        ])
        ->dateField('eventDate', [
            'displayType' => 'datePicker',
        ])
        ->tableField('lineItems', [
            'columns' => [
                'col1' => [
                    'heading' => 'Item',
                    'type' => 'singleline',
                ],
            ],
        ])
        ->checkboxesField('topics', [
            'options' => [
                ['label' => 'One', 'value' => 'one'],
            ],
        ])
        ->hiddenField('trackingToken', [
            'defaultOption' => 'cookie',
            'cookieName' => 'utm_source',
        ])
        ->create();

    $builder = Formie::$plugin->getClientModuleManifestBuilder();
    $frontendModules = $builder->buildCanonical($form, ClientModule::RENDER_TARGET_FRONTEND);
    $cpModules = $builder->buildCanonical($form, ClientModule::RENDER_TARGET_CP_EDIT);
    $frontendModuleIds = array_values(array_map(static fn(array $module): string => (string)$module['id'], $frontendModules));
    $cpModuleIds = array_values(array_map(static fn(array $module): string => (string)$module['id'], $cpModules));
    $cpConfigModuleIds = array_values(array_map(static fn(array $module): string => (string)$module['id'], $form->getClientConfig()['modules'] ?? []));
    $frontendTextLimit = current(array_filter($frontendModules, static fn(array $module): bool => ($module['id'] ?? null) === 'text-limit')) ?: null;
    $cpTextLimit = current(array_filter($cpModules, static fn(array $module): bool => ($module['id'] ?? null) === 'text-limit')) ?: null;

    expect($frontendModuleIds)
        ->toContain('text-limit')
        ->toContain('rich-text')
        ->toContain('date-picker')
        ->toContain('table')
        ->toContain('checkbox-radio')
        ->toContain('hidden');

    expect($cpModuleIds)
        ->toContain('rich-text')
        ->toContain('date-picker')
        ->not->toContain('table')
        ->not->toContain('checkbox-radio')
        ->not->toContain('hidden')
        ->and($cpConfigModuleIds)->toBe($cpModuleIds)
        ->and($frontendTextLimit['config']['allowOvertype'] ?? false)->toBeFalse()
        ->and($cpTextLimit['config']['allowOvertype'] ?? false)->toBeTrue();
});

it('filters conditions out of cp edit manifests when the form shows all fields', function (): void {
    $form = formie()->conditionForms()->optionsValueVisibility([
        'title' => 'CP Conditions Render Target',
    ]);
    $form->settings->cpSubmissionFieldConditions = 'show-all';
    $form->setSettings($form->settings);

    $builder = Formie::$plugin->getClientModuleManifestBuilder();
    $frontendModuleIds = array_values(array_map(static fn(array $module): string => (string)$module['id'], $builder->buildCanonical($form, ClientModule::RENDER_TARGET_FRONTEND)));
    $cpModuleIds = array_values(array_map(static fn(array $module): string => (string)$module['id'], $builder->buildCanonical($form, ClientModule::RENDER_TARGET_CP_EDIT)));

    expect($frontendModuleIds)
        ->toContain('conditions')
        ->and($cpModuleIds)->not->toContain('conditions');
});

it('includes conditions in cp edit manifests when the form follows field conditions', function (): void {
    $form = formie()->conditionForms()->optionsValueVisibility([
        'title' => 'CP Conditions Follow Target',
    ]);

    $builder = Formie::$plugin->getClientModuleManifestBuilder();
    $cpModules = $builder->buildCanonical($form, ClientModule::RENDER_TARGET_CP_EDIT);
    $cpModuleIds = array_values(array_map(static fn(array $module): string => (string)$module['id'], $cpModules));
    $conditionsModule = current(array_filter($cpModules, static fn(array $module): bool => ($module['id'] ?? null) === 'conditions')) ?: null;

    expect($cpModuleIds)
        ->toContain('conditions')
        ->and($conditionsModule['config']['cpDisplayMode'] ?? null)->toBe('hide');
});

it('uses muted cp display mode when configured on the form', function (): void {
    $form = formie()->conditionForms()->optionsValueVisibility([
        'title' => 'CP Conditions Muted Target',
    ]);
    $form->settings->cpSubmissionFieldConditions = 'muted';
    $form->setSettings($form->settings);

    $builder = Formie::$plugin->getClientModuleManifestBuilder();
    $cpModules = $builder->buildCanonical($form, ClientModule::RENDER_TARGET_CP_EDIT);
    $conditionsModule = current(array_filter($cpModules, static fn(array $module): bool => ($module['id'] ?? null) === 'conditions')) ?: null;

    expect($conditionsModule['config']['cpDisplayMode'] ?? null)->toBe('muted');
});
