<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\models\BrowserModule;

it('filters frontend-only field modules out of cp edit manifests and config', function (): void {
    $volume = \Tests\Support\UploadTestHelper::ensureUploadVolume();
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
        ->passwordField('password', ['passwordMinLength' => 8])
        ->dropdownField('choice', ['useSearchable' => true])
        ->summaryField('summary')
        ->fileUploadField('file', ['displayType' => 'uploadManager', 'uploadLocationSource' => 'volume:' . $volume->uid])
        ->fileUploadField('simpleFile', ['displayType' => 'file', 'uploadLocationSource' => 'volume:' . $volume->uid])
        ->radioField('radio')
        ->recipientsField('recipients', ['displayType' => 'dropdown', 'useSearchable' => true])
        ->addField(\verbb\formie\fields\Survey::class, 'ranking', ['displayType' => 'rank'])
        ->addField(\verbb\formie\fields\Survey::class, 'rating', ['displayType' => 'rating'])
        ->addField(\verbb\formie\fields\Survey::class, 'likert', ['displayType' => 'likert'])
        ->addField(\verbb\formie\fields\Quiz::class, 'quiz', ['displayType' => 'checkboxes'])
        ->create();

    // A single CP picker is still module-owned; paired date/time fields use
    // Craft's native controls and should not declare the browser module.
    $form->getFieldByHandle('eventDate')->getFieldByHandle('time')->enabled = false;

    $builder = Formie::$plugin->getBrowserModuleManifestBuilder();
    $frontendModules = $builder->buildForSurface($form, BrowserModule::SURFACE_SERVER_RENDERED)->toArray()['entries'];
    $cpModules = $builder->buildForSurface($form, BrowserModule::SURFACE_CP_EDIT)->toArray()['entries'];
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
        ->toContain('formie:hidden')
        ->toContain('formie:summary')
        ->toContain('formie:password-validation')
        ->toContain('formie:combobox');

    expect($cpModuleIds)
        ->toContain('formie:rich-text')
        ->toContain('formie:date-picker')
        ->not->toContain('formie:upload-manager')
        ->not->toContain('formie:file-upload')
        ->not->toContain('formie:survey-rank')
        ->not->toContain('formie:survey-rating')
        ->not->toContain('formie:survey-likert')
        ->not->toContain('formie:table')
        ->not->toContain('formie:checkbox-radio')
        ->not->toContain('formie:hidden')
        ->not->toContain('formie:summary')
        ->not->toContain('formie:password-validation')
        ->not->toContain('formie:combobox')
        ->and($cpConfigModuleIds)->toBe($cpModuleIds)
        ->and($frontendTextLimit['config']['allowOvertype'] ?? false)->toBeFalse()
        ->and($cpTextLimit['config']['allowOvertype'] ?? false)->toBeTrue();
});

it('does not declare the date picker for paired cp date and time controls', function (): void {
    $form = formie()
        ->form(['title' => 'CP Date Time Render Target'])
        ->dateField('eventDate', [
            'displayType' => 'datePicker',
        ])
        ->create();

    $cpModuleIds = array_column(
        Formie::$plugin->getBrowserModuleManifestBuilder()->buildForSurface($form, BrowserModule::SURFACE_CP_EDIT)->toArray()['entries'],
        'moduleId',
    );

    expect($form->getFieldByHandle('eventDate')->getIsDateTime())->toBeTrue()
        ->and($cpModuleIds)->not->toContain('formie:date-picker');
});

it('filters conditions out of cp edit manifests when the form shows all fields', function (): void {
    $form = formie()->conditionForms()->optionsValueVisibility([
        'title' => 'CP Conditions Render Target',
    ]);
    $form->settings->cpSubmissionFieldConditions = 'show-all';
    $form->settings->setAttributes($form->settings->getAttributes());

    $builder = Formie::$plugin->getBrowserModuleManifestBuilder();
    $frontendModuleIds = array_values(array_map(static fn(array $module): string => (string)$module['moduleId'], $builder->buildForSurface($form, BrowserModule::SURFACE_SERVER_RENDERED)->toArray()['entries']));
    $cpModuleIds = array_values(array_map(static fn(array $module): string => (string)$module['moduleId'], $builder->buildForSurface($form, BrowserModule::SURFACE_CP_EDIT)->toArray()['entries']));

    expect($frontendModuleIds)
        ->toContain('formie:conditions')
        ->and($cpModuleIds)->not->toContain('formie:conditions');
});

it('includes conditions in cp edit manifests when the form follows field conditions', function (): void {
    $form = formie()->conditionForms()->optionsValueVisibility([
        'title' => 'CP Conditions Follow Target',
    ]);

    $builder = Formie::$plugin->getBrowserModuleManifestBuilder();
    $cpModules = $builder->buildForSurface($form, BrowserModule::SURFACE_CP_EDIT)->toArray()['entries'];
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
    $cpModules = $builder->buildForSurface($form, BrowserModule::SURFACE_CP_EDIT)->toArray()['entries'];
    $conditionsModule = current(array_filter($cpModules, static fn(array $module): bool => ($module['moduleId'] ?? null) === 'formie:conditions')) ?: null;

    expect($conditionsModule['config']['cpDisplayMode'] ?? null)->toBe('muted');
});

it('renders dynamic address controls for submission editor modules', function (): void {
    $rows = (new \verbb\formie\fields\Address())->getSubFields();
    $form = formie()->form()->addressField('address', ['rows' => $rows])->create();
    $state = $form->getFieldByHandle('address')->getFieldByHandle('state');
    $state->inputMode = \verbb\formie\fields\subfields\AddressState::INPUT_MODE_DROPDOWN_WHEN_AVAILABLE;
    $state->limit = true;
    $state->max = 30;
    $state->maxType = 'characters';

    $builder = Formie::$plugin->getBrowserModuleManifestBuilder();
    foreach ([BrowserModule::SURFACE_SERVER_RENDERED, BrowserModule::SURFACE_CLIENT_RENDERED] as $surface) {
        expect(array_column($builder->buildForSurface($form, $surface)->toArray()['entries'], 'moduleId'))
            ->toContain('formie:address-state');
    }

    $cpModuleIds = array_column($builder->buildForSurface($form, BrowserModule::SURFACE_CP_EDIT)->toArray()['entries'], 'moduleId');
    expect($cpModuleIds)->toContain('formie:address-state')->toContain('formie:text-limit')
        ->and(array_column($form->getCpEditConfig()['modules']['entries'], 'moduleId'))->toBe($cpModuleIds);

    \Tests\Support\WebRequestTestHelper::withWebRequestContext(function () use ($form, $state): void {
        Craft::$app->getRequest()->setIsCpRequest(true);
        $submission = new \verbb\formie\elements\Submission();
        $submission->setForm($form);
        $html = $state->getSubmissionHtml('Victoria', $submission);
        $document = new DOMDocument();
        @$document->loadHTML((string)$html);
        $inputs = (new DOMXPath($document))->query('//input[@type="text"]');

        expect($inputs->length)->toBe(1)
            ->and($inputs->item(0)->getAttribute('value'))->toBe('Victoria')
            ->and($inputs->item(0)->hasAttribute('disabled'))->toBeFalse()
            ->and($inputs->item(0)->hasAttribute('data-formie-address-state-dynamic'))->toBeTrue()
            ->and($inputs->item(0)->hasAttribute('data-formie-address-state-cp'))->toBeTrue()
            ->and($inputs->item(0)->getAttribute('id'))->toBe('state');

        $country = $form->getFieldByHandle('address')->getFieldByHandle('country');
        @$document->loadHTML((string)$country->getSubmissionHtml('AU', $submission));
        $selects = (new DOMXPath($document))->query('//select[@data-formie-address-country-input]');
        expect($selects->length)->toBe(1)
            ->and((new DOMXPath($document))->query('//option[@selected]')->item(0)->getAttribute('value'))->toBe('AU');

        $state->inputMode = \verbb\formie\fields\subfields\AddressState::INPUT_MODE_TEXT;
        @$document->loadHTML((string)$state->getSubmissionHtml('Victoria', $submission));
        expect((new DOMXPath($document))->query('//input[@data-formie-address-state-dynamic]')->length)->toBe(0);
    });
});
