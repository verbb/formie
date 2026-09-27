<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\models\BrowserModuleEntry;

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

    $builder = Formie::$plugin->getBrowserModuleManifestBuilder();
    $frontendModules = $builder->buildCanonical($form, BrowserModuleEntry::SURFACE_SERVER_RENDERED)['entries'];
    $cpModules = $builder->buildCanonical($form, BrowserModuleEntry::SURFACE_CP_EDIT)['entries'];
    $frontendModuleIds = array_values(array_map(static fn(array $module): string => (string)$module['moduleId'], $frontendModules));
    $cpModuleIds = array_values(array_map(static fn(array $module): string => (string)$module['moduleId'], $cpModules));
    $cpConfigModuleIds = array_values(array_map(static fn(array $module): string => (string)$module['moduleId'], $form->getCpEditConfig()['modules']['entries'] ?? []));
    $frontendTextLimit = current(array_filter($frontendModules, static fn(array $module): bool => ($module['moduleId'] ?? null) === 'formie:text-limit')) ?: null;
    $cpTextLimit = current(array_filter($cpModules, static fn(array $module): bool => ($module['moduleId'] ?? null) === 'formie:text-limit')) ?: null;

    expect($frontendModuleIds)
        ->toContain('formie:text-limit')
        ->toContain('formie:rich-text')
        ->toContain('formie:date-picker')
        ->toContain('formie:table')
        ->toContain('formie:checkbox-radio')
        ->toContain('formie:hidden');

    expect($cpModuleIds)
        ->toContain('formie:rich-text')
        ->toContain('formie:date-picker')
        ->not->toContain('formie:table')
        ->not->toContain('formie:checkbox-radio')
        ->not->toContain('formie:hidden')
        ->and($cpConfigModuleIds)->toBe($cpModuleIds)
        ->and($frontendTextLimit['config']['allowOvertype'] ?? false)->toBeFalse()
        ->and($cpTextLimit['config']['allowOvertype'] ?? false)->toBeTrue();
});

it('filters conditions out of cp edit manifests when the form shows all fields', function (): void {
    $form = formie()->conditionForms()->optionsValueVisibility([
        'title' => 'CP Conditions Render Target',
    ]);
    $form->settings->cpSubmissionFieldConditions = 'show-all';
    $form->settings->setAttributes($form->settings->getAttributes());

    $builder = Formie::$plugin->getBrowserModuleManifestBuilder();
    $frontendModuleIds = array_values(array_map(static fn(array $module): string => (string)$module['moduleId'], $builder->buildCanonical($form, BrowserModuleEntry::SURFACE_SERVER_RENDERED)['entries']));
    $cpModuleIds = array_values(array_map(static fn(array $module): string => (string)$module['moduleId'], $builder->buildCanonical($form, BrowserModuleEntry::SURFACE_CP_EDIT)['entries']));

    expect($frontendModuleIds)
        ->toContain('formie:conditions')
        ->and($cpModuleIds)->not->toContain('formie:conditions');
});

it('includes conditions in cp edit manifests when the form follows field conditions', function (): void {
    $form = formie()->conditionForms()->optionsValueVisibility([
        'title' => 'CP Conditions Follow Target',
    ]);

    $builder = Formie::$plugin->getBrowserModuleManifestBuilder();
    $cpModules = $builder->buildCanonical($form, BrowserModuleEntry::SURFACE_CP_EDIT)['entries'];
    $cpModuleIds = array_values(array_map(static fn(array $module): string => (string)$module['moduleId'], $cpModules));
    $conditionsModule = current(array_filter($cpModules, static fn(array $module): bool => ($module['moduleId'] ?? null) === 'formie:conditions')) ?: null;

    expect($cpModuleIds)
        ->toContain('formie:conditions')
        ->and($conditionsModule['config']['cpDisplayMode'] ?? null)->toBe('hide');
});

it('uses muted cp display mode when configured on the form', function (): void {
    $form = formie()->conditionForms()->optionsValueVisibility([
        'title' => 'CP Conditions Muted Target',
    ]);
    $form->settings->cpSubmissionFieldConditions = 'muted';
    $form->settings->setAttributes($form->settings->getAttributes());

    $builder = Formie::$plugin->getBrowserModuleManifestBuilder();
    $cpModules = $builder->buildCanonical($form, BrowserModuleEntry::SURFACE_CP_EDIT)['entries'];
    $conditionsModule = current(array_filter($cpModules, static fn(array $module): bool => ($module['moduleId'] ?? null) === 'formie:conditions')) ?: null;

    expect($conditionsModule['config']['cpDisplayMode'] ?? null)->toBe('muted');
});
