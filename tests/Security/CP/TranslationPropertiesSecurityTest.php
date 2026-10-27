<?php

declare(strict_types=1);

use craft\db\Query;
use verbb\formie\Formie;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\Table;
use verbb\formie\records\FieldSiteOverride;
use verbb\formie\services\Fields;
use yii\base\Event;

beforeEach(function (): void {
    if (!Craft::$app->getIsMultiSite()) {
        test()->markTestSkipped('Requires site translations; covered by the multisite suite.');
    }
});

class DeclaredTranslationSecurityField extends SingleLineText
{
    public string $customText = 'Canonical custom text';
    public static function translatableProperties(): array
    {
        return [...parent::translatableProperties(), 'customText'];
    }
}

function translationPropertyFixture(): array
{
    \Tests\Support\UploadTestHelper::ensureUploadVolume();
    $rows = [['fields' => [['type' => SingleLineText::class, 'handle' => 'nested', 'label' => 'Nested']]]];
    $form = formie()->form(['handle' => 'translationKeys' . bin2hex(random_bytes(5))])
        ->singleLineTextField('name', ['label' => 'Name', 'required' => true])
        ->fileUploadField('documents', ['restrictFiles' => true])
        ->groupField('details', ['rows' => $rows])->repeaterField('items', ['rows' => $rows])->create();
    $service = Formie::$plugin->getFormSiteOverrides();
    $siteId = current(array_filter(Formie::$plugin->getFormSitePropagation()->resolveSiteIdsForForm($form), fn($id) => $id !== $service->getSourceSiteId($form)));
    expect($siteId)->not->toBeFalse();
    return [$form, (int)$siteId];
}

it('filters new definition translation writes while keeping declared text', function(): void {
    [$form, $siteId] = translationPropertyFixture();
    $field = $form->getFieldByHandle('documents');
    Formie::$plugin->getFormSiteOverrides()->saveTranslationBundle($form->id, $siteId, [
        'fieldOverrides' => [$field->definitionId => ['label' => 'Documents traduits', 'restrictFiles' => false, 'handle' => 'changedHandle', 'required' => false]],
    ]);
    expect(Formie::$plugin->getFieldSiteOverrides()->getOverride($field->definitionId, $siteId))->toBe(['label' => 'Documents traduits']);
})->group('security');

it('keeps historical undeclared bytes but ignores them in runtime and builder fields', function(string $route): void {
    [$form, $siteId] = translationPropertyFixture();
    $field = $form->getFieldByHandle('documents');
    $record = new FieldSiteOverride(['fieldId' => $field->definitionId, 'siteId' => $siteId,
        'overrides' => ['label' => 'Translated upload', 'restrictFiles' => false, 'handle' => 'changedHandle']]);
    expect($record->save(false))->toBeTrue();
    $before = (new Query())->from(Table::FORMIE_FIELD_SITE_OVERRIDES)->where(['id' => $record->id])->one();
    $service = Formie::$plugin->getFormSiteOverrides();
    if ($route === 'runtime') {
        $translated = $service->applyToForm($form, $siteId, true);
        $actual = $translated->getFields()[1];
        expect($actual->label)->toBe('Translated upload')->and($actual->handle)->toBe('documents')->and($actual->restrictFiles)->toBeTrue();
    } else {
        $data = $service->applyToBuilderData($form->getFormBuilderConfig(), $siteId);
        $actual = $data['pages'][0]['rows'][0]['fields'][1];
        expect($actual['label'])->toBe('Translated upload')->and($actual['handle'])->toBe('documents')->and($actual['restrictFiles'])->toBeTrue();
    }
    expect((new Query())->from(Table::FORMIE_FIELD_SITE_OVERRIDES)->where(['id' => $record->id])->one())->toBe($before);
    Formie::$plugin->getFieldSiteOverrides()->saveOverride($field->definitionId, $siteId, ['placeholder' => 'Choose files']);
    $stored = Formie::$plugin->getFieldSiteOverrides()->getOverride($field->definitionId, $siteId);
    expect($stored['restrictFiles'])->toBeFalse()->and($stored['handle'])->toBe('changedHandle')->and($stored['placeholder'])->toBe('Choose files');
})->with(['runtime', 'builder'])->group('security');

it('filters nested Group and Repeater definition properties without blocking labels', function(): void {
    [$form, $siteId] = translationPropertyFixture();
    $fields = [];
    foreach (['details', 'items'] as $handle) {
        $field = $form->getFieldByHandle($handle)->getFieldLayout()->getPages()[0]->getRows()[0]->getFields()[0];
        $fields[$handle] = $field;
        expect((new FieldSiteOverride(['fieldId' => $field->definitionId, 'siteId' => $siteId, 'overrides' => ['label' => 'Nested translation', 'handle' => 'changedNested']]))->save(false))->toBeTrue();
    }
    $translated = Formie::$plugin->getFormSiteOverrides()->applyToForm($form, $siteId, true);
    foreach ($fields as $handle => $field) {
        $nested = $translated->getFieldByHandle($handle)->getFieldLayout()->getPages()[0]->getRows()[0]->getFields()[0];
        expect($nested->label)->toBe('Nested translation')->and($nested->handle)->toBe('nested');
    }
})->group('security');

it('preserves separate required placement translations and declared validation messages', function(): void {
    [$form, $siteId] = translationPropertyFixture();
    $field = $form->getFieldByHandle('name');
    Formie::$plugin->getFormSiteOverrides()->saveTranslationBundle($form->id, $siteId, [
        'fieldOverrides' => [$field->definitionId => ['required' => true, 'validationMessages' => ['required' => 'Translated required']]],
        'fieldInstanceOverrides' => [$field->reference => ['required' => false]],
    ]);
    $translated = Formie::$plugin->getFormSiteOverrides()->applyToForm($form, $siteId, true)->getFieldByHandle('name');
    expect($translated->required)->toBeFalse()->and($translated->validationMessages['required'])->toBe('Translated required')->and($field->required)->toBeTrue();
})->group('security');

it('honors declared custom properties through persistence runtime and builder data', function(): void {
    $listener = function($event): void { $event->fields[] = DeclaredTranslationSecurityField::class; };
    Event::on(Fields::class, Fields::EVENT_REGISTER_FIELDS, $listener);
    Formie::$plugin->getFields()->resetFieldRegistryCache();
    try {
        $form = formie()->form(['handle' => 'customTranslation' . bin2hex(random_bytes(5))])->addFieldConfig(['type' => DeclaredTranslationSecurityField::class, 'handle' => 'custom', 'label' => 'Custom'])->create();
        $service = Formie::$plugin->getFormSiteOverrides();
        $siteId = (int)current(array_filter(Formie::$plugin->getFormSitePropagation()->resolveSiteIdsForForm($form), fn($id) => $id !== $service->getSourceSiteId($form)));
        $field = $form->getFieldByHandle('custom');
        Formie::$plugin->getFieldSiteOverrides()->saveOverride($field->definitionId, $siteId, ['customText' => 'Translated custom text']);
        expect($service->applyToForm($form, $siteId, true)->getFieldByHandle('custom')->customText)->toBe('Translated custom text');
        $builder = $service->applyToBuilderData($form->getFormBuilderConfig(), $siteId);
        expect($builder['pages'][0]['rows'][0]['fields'][0]['customText'])->toBe('Translated custom text');
        $definition = new \verbb\formie\records\FieldDefinitionRecord(['type' => DeclaredTranslationSecurityField::class, 'label' => 'Unplaced', 'handle' => 'unplaced' . bin2hex(random_bytes(4)), 'settings' => []]);
        expect($definition->save(false))->toBeTrue();
        Formie::$plugin->getFieldSiteOverrides()->saveOverride($definition->id, $siteId, ['customText' => 'Unplaced translated text']);
        expect(Formie::$plugin->getFieldSiteOverrides()->getOverride($definition->id, $siteId)['customText'] ?? null)->toBe('Unplaced translated text');
    } finally {
        Event::off(Fields::class, Fields::EVENT_REGISTER_FIELDS, $listener);
        Formie::$plugin->getFields()->resetFieldRegistryCache();
    }
})->group('security');

it('retains missing custom field translations across import and filters them after registration', function(): void {
    $listener = function($event): void { $event->fields[] = DeclaredTranslationSecurityField::class; };
    Event::on(Fields::class, Fields::EVENT_REGISTER_FIELDS, $listener);
    Formie::$plugin->getFields()->resetFieldRegistryCache();
    try {
        $form = formie()->form(['handle' => 'portableTranslation' . bin2hex(random_bytes(5))])->addFieldConfig(['type' => DeclaredTranslationSecurityField::class, 'handle' => 'custom', 'label' => 'Custom'])->create();
        $service = Formie::$plugin->getFormSiteOverrides();
        $siteId = (int)current(array_filter(Formie::$plugin->getFormSitePropagation()->resolveSiteIdsForForm($form), fn($id) => $id !== $service->getSourceSiteId($form)));
        $field = $form->getFieldByHandle('custom');
        $values = ['label' => 'Translated label', 'customText' => 'Portable translation', 'handle' => 'forgedHandle'];
        expect((new FieldSiteOverride(['fieldId' => $field->definitionId, 'siteId' => $siteId, 'overrides' => $values]))->save(false))->toBeTrue();
        $export = \verbb\formie\helpers\ImportExportHelper::generateFormExport($form);
        Event::off(Fields::class, Fields::EVENT_REGISTER_FIELDS, $listener);
        Formie::$plugin->getFields()->resetFieldRegistryCache();

        $imported = \verbb\formie\helpers\ImportExportHelper::createFromImport($export)->form;
        $missing = $imported->getFields()[0];
        expect($missing)->toBeInstanceOf(\verbb\formie\fields\MissingField::class);
        $stored = Formie::$plugin->getFieldSiteOverrides()->getOverride($missing->definitionId, $siteId);
        expect($stored)->toEqual($values);
        expect($service->getBuilderMultiSiteConfig($imported, $siteId)['fieldOverrides'][$siteId])->toBe([]);
        expect($service->applyToForm($imported, $siteId, true)->getFields()[0]->label)->toBe('Custom');

        Event::on(Fields::class, Fields::EVENT_REGISTER_FIELDS, $listener);
        Formie::$plugin->getFields()->resetFieldRegistryCache();
        expect($service->filterFieldTranslationProperties(DeclaredTranslationSecurityField::class, $stored))->toBe(['label' => 'Translated label', 'customText' => 'Portable translation']);
    } finally {
        Event::off(Fields::class, Fields::EVENT_REGISTER_FIELDS, $listener);
        Formie::$plugin->getFields()->resetFieldRegistryCache();
    }
})->group('security');

it('leaves unavailable and untyped builder configurations untouched', function(string $type): void {
    $field = ['fieldId' => 999999999, 'handle' => 'missing', 'label' => 'Original'];
    if ($type !== '') { $field['type'] = $type; }
    $canonical = ['pages' => [['rows' => [['fields' => [$field]]]]]];
    $merged = Formie::$plugin->getFormSiteOverrides()->mergeOverridesIntoBuilderData($canonical, [], [999999999 => ['label' => 'Changed', 'handle' => 'forged']]);
    expect($merged)->toBe($canonical);
})->with(['', 'UnavailableTranslationField', \verbb\formie\fields\MissingField::class, DeclaredTranslationSecurityField::class])->group('security');

it('filters the separate form and stencil site-switch bundles without deleting stored overrides', function(string $kind): void {
    [$form, $siteId] = translationPropertyFixture();
    $field = $form->getFieldByHandle('name');
    $overrides = ['label' => 'Translated name', 'handle' => 'forgedHandle'];
    if ($kind === 'form') {
        expect((new FieldSiteOverride(['fieldId' => $field->definitionId, 'siteId' => $siteId, 'overrides' => $overrides]))->save(false))->toBeTrue();
        $config = Formie::$plugin->getFormSiteOverrides()->getBuilderMultiSiteConfig($form, $siteId);
        expect($config['fieldOverrides'][$siteId][$field->definitionId])->toBe(['label' => 'Translated name']);
        expect(Formie::$plugin->getFieldSiteOverrides()->getOverride($field->definitionId, $siteId))->toBe($overrides);
    } else {
        $stencil = new \verbb\formie\models\Stencil(['name' => 'Translation keys', 'handle' => 'translationKeys' . bin2hex(random_bytes(5))]);
        $stencil->data->populateFormData($form);
        $site = Craft::$app->getSites()->getSiteById($siteId);
        $stencil->data->translations = [$site->uid => ['fieldOverrides' => [$field->reference => $overrides]]];
        $config = Formie::$plugin->getStencils()->getBuilderMultiSiteConfig($stencil, $siteId);
        expect($config['fieldOverrides'][$siteId][$field->reference])->toBe(['label' => 'Translated name']);
        expect($stencil->data->translations[$site->uid]['fieldOverrides'][$field->reference])->toBe($overrides);
    }
})->with(['form', 'stencil'])->group('security');
