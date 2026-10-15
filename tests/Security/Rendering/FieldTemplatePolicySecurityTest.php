<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\fields\Hidden;
use verbb\formie\fields\Html;
use verbb\formie\helpers\FieldBuilderPolicy;
use verbb\formie\models\Settings;
use Twig\Sandbox\SecurityError;

beforeEach(function (): void {
    Formie::$plugin->getSettings()->setAttributes([
        'allowHiddenFieldTemplates' => false,
        'allowHtmlFieldTwig' => false,
        'allowHtmlFieldTemplateIncludes' => false,
        'requireHtmlPurification' => true,
    ], false);
});

it('defaults fresh sites to no field Twig and mandatory purification', function (): void {
    $settings = new Settings();
    expect($settings->allowHiddenFieldTemplates)->toBeFalse()
        ->and($settings->allowHtmlFieldTwig)->toBeFalse()
        ->and($settings->allowHtmlFieldTemplateIncludes)->toBeFalse()
        ->and($settings->requireHtmlPurification)->toBeTrue();
    expect(FieldBuilderPolicy::templateSettingsForSchema(null))->toBe([])
        ->and(FieldBuilderPolicy::templateSettingsForSchema('4.0.82'))->toBe([])
        ->and(FieldBuilderPolicy::templateSettingsForSchema('3.0.0'))->toBe(FieldBuilderPolicy::legacyTemplateSettings());
});

it('blocks Hidden execution before cache lookup without changing its authoritative source', function (): void {
    $field = new Hidden(['handle' => 'policy' . uniqid(), 'valueSource' => 'template', 'defaultTemplate' => '{{ 6 * 7 }}']);
    expect($field->getDefaultValue())->toBe('')
        ->and($field->normalizeValueFromRequest('forged', null))->toBe('')
        ->and($field->validate(['valueSource']))->toBeFalse()
        ->and($field->valueSource)->toBe('template')
        ->and($field->defaultTemplate)->toBe('{{ 6 * 7 }}');
    Formie::$plugin->getSettings()->allowHiddenFieldTemplates = true;
    expect($field->getDefaultValue())->toBe('42');
    Formie::$plugin->getSettings()->allowHiddenFieldTemplates = false;
    expect($field->getDefaultValue())->toBe('');
});

it('keeps references available in Custom Value with Hidden templates disabled', function (): void {
    $form = formie()->form(['handle' => 'policyReferences'])->hiddenField('context', [
        'valueSource' => 'custom', 'defaultValue' => '{form:handle}',
    ])->create();
    $submission = formie()->submission($form)->save();
    expect($form->getFieldByHandle('context')->getInitialValue($submission))->toBe('policyReferences');
});

it('blocks stored and subsequently assigned HTML Twig without exposing source', function (): void {
    $field = new Html(['allowTwig' => true, 'htmlContent' => '{{ 6 * 7 }}']);
    expect($field->getRenderedHtmlContent())->toBe('')
        ->and($field->validate(['allowTwig']))->toBeFalse()
        ->and($field->allowTwig)->toBeTrue()
        ->and($field->htmlContent)->toBe('{{ 6 * 7 }}');
    Formie::$plugin->getSettings()->allowHtmlFieldTwig = true;
    expect($field->getRenderedHtmlContent())->toBe('42');
    Formie::$plugin->getSettings()->allowHtmlFieldTwig = false;
    $field->allowTwig = true;
    expect($field->getRenderedHtmlContent())->toBe('');
});

it('requires purification regardless of saved or runtime field preference', function (): void {
    $field = new Html(['purifyContent' => false, 'htmlContent' => '<script>alert(1)</script><p>Kept</p>']);
    expect($field->getRenderedHtmlContent())->toBe('<p>Kept</p>')
        ->and($field->purifyContent)->toBeFalse();
    Formie::$plugin->getSettings()->requireHtmlPurification = false;
    expect($field->getRenderedHtmlContent())->toContain('<script>');
    $field->purifyContent = true;
    expect($field->getRenderedHtmlContent())->toBe('<p>Kept</p>');
});

it('requires purification after enabled Twig renders', function (): void {
    Formie::$plugin->getSettings()->allowHtmlFieldTwig = true;
    $field = new Html(['allowTwig' => true, 'purifyContent' => false, 'htmlContent' => '{{ markup|raw }}']);
    expect($field->getRenderedHtmlContent(['markup' => '<script>alert(1)</script><p>Kept</p>']))->toBe('<p>Kept</p>');
});

it('revokes includes even after warming the HTML include environment', function (): void {
    $settings = Formie::$plugin->getSettings();
    $settings->allowHtmlFieldTwig = true;
    $settings->allowHtmlFieldTemplateIncludes = true;
    $templates = Formie::$plugin->getTemplates();
    $source = '{% include "missing-policy-template" ignore missing %}';
    expect($templates->renderHtmlFieldTemplate($source))->toBe('');
    $settings->allowHtmlFieldTemplateIncludes = false;
    expect(fn() => $templates->renderHtmlFieldTemplate($source))->toThrow(SecurityError::class);
    expect(fn() => $templates->renderHtmlFieldTemplate('{{ include("missing-policy-template", ignore_missing: true) }}'))->toThrow(SecurityError::class);
    expect($templates->renderHtmlFieldTemplate('{{ 6 * 7 }}'))->toBe('42');
    $settings->allowHtmlFieldTwig = false;
    $settings->allowHtmlFieldTemplateIncludes = true;
    expect(FieldBuilderPolicy::allowHtmlFieldTemplateIncludes())->toBeFalse();
    expect($templates->renderHtmlFieldTemplate($source))->toBe('');
});

it('removes restricted choices from new field schemas', function (): void {
    $hiddenSchema = array_column((new Hidden())->defineFormBuilderGeneralSchema(), null, 'name');
    expect(array_column($hiddenSchema['valueSource']['options'], 'value'))->not->toContain('template')
        ->and($hiddenSchema)->not->toHaveKey('defaultTemplate');
    $htmlSchema = array_column((new Html())->defineFormBuilderAdvancedSchema(), null, 'name');
    expect((new Html())->defineFormBuilderPreviewSchema()[0]['fallbackHtml'])->toBe('');
    expect($htmlSchema['allowTwig']['if'])->toBe('allowTwig')
        ->and($htmlSchema)->not->toHaveKey('purifyContent');
    Formie::$plugin->getSettings()->allowHtmlFieldTwig = true;
    Formie::$plugin->getSettings()->requireHtmlPurification = false;
    $enabledSchema = array_column((new Html())->defineFormBuilderAdvancedSchema(), null, 'name');
    expect($enabledSchema['allowTwig'])->not->toHaveKey('if')
        ->and($enabledSchema)->toHaveKey('purifyContent');
});

it('rejects disabled template capabilities at form persistence', function (string $type): void {
    $builder = formie()->form();
    if ($type === 'hidden') {
        $builder->hiddenField('computed', ['valueSource' => 'template', 'defaultTemplate' => '{{ 6 * 7 }}']);
    } else {
        $builder->htmlField('computed', ['allowTwig' => true, 'htmlContent' => '{{ 6 * 7 }}']);
    }
    expect(fn() => $builder->create())->toThrow(\RuntimeException::class, 'disabled in Settings');
})->with(['hidden', 'html']);

it('rejects imports and copies that attempt to retain disabled Twig modes', function (string $operation): void {
    $settings = Formie::$plugin->getSettings();
    $settings->allowHiddenFieldTemplates = true;
    $settings->allowHtmlFieldTwig = true;
    $form = formie()->form()->hiddenField('computed', ['valueSource' => 'template', 'defaultTemplate' => '{{ 6 * 7 }}'])
        ->htmlField('markup', ['allowTwig' => true, 'htmlContent' => '{{ 6 * 7 }}'])->create();
    $export = \verbb\formie\helpers\ImportExportHelper::generateFormExport($form);
    $settings->allowHiddenFieldTemplates = false;
    $settings->allowHtmlFieldTwig = false;
    if ($operation === 'import') {
        expect(fn() => \verbb\formie\helpers\ImportExportHelper::importFormFromJson($export, 'create'))
            ->toThrow(\Exception::class, 'disabled in Settings');
    } else {
        try {
            Formie::$plugin->getForms()->duplicateForm($form);
            test()->fail('Disabled Twig fields were duplicated.');
        } catch (\craft\errors\InvalidElementException $e) {
            expect(json_encode($e->element->getErrors()))->toContain('disabled in Settings');
        }
    }
})->with(['import', 'duplicate']);
