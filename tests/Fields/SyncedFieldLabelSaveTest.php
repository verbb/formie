<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\fields\SingleLineText;

function syncedFieldLabelSaveHandle(string $prefix): string
{
    static $counter = 0;

    return $prefix . (++$counter) . uniqid();
}

it('persists synced field label changes when saving a form', function (): void {
    $sourceForm = formie()
        ->form([
            'title' => 'Sync Label Source',
            'handle' => syncedFieldLabelSaveHandle('syncLabelSource'),
        ])
        ->emailField('email', ['label' => 'Original Email'])
        ->create();

    $sourceField = $sourceForm->getFieldByHandle('email');
    $definitionId = (int)$sourceField->fieldId;

    $syncedFieldConfig = syncedFieldLabelSaveStripImportedMeta($sourceField->getFormBuilderConfig());
    $syncedFieldConfig['fieldId'] = $definitionId;
    $syncedFieldConfig['syncId'] = $definitionId;
    $syncedFieldConfig['isSynced'] = true;

    $targetForm = formie()
        ->form([
            'title' => 'Sync Label Target',
            'handle' => syncedFieldLabelSaveHandle('syncLabelTarget'),
        ])
        ->addFieldConfig($syncedFieldConfig)
        ->create();

    $reloadedSource = Form::find()->id($sourceForm->id)->one();
    $pages = $reloadedSource->getFormLayout()->getFormBuilderConfig();
    $pages[0]['rows'][0]['fields'][0]['label'] = 'Updated Email Label';

    $reloadedSource->getFormLayout()->setPages($pages);

    expect(Craft::$app->elements->saveElement($reloadedSource))->toBeTrue();

    $reloadedSource = Form::find()->id($sourceForm->id)->one();
    $reloadedTarget = Form::find()->id($targetForm->id)->one();

    expect($reloadedSource?->getFieldByHandle('email')->label)->toBe('Updated Email Label')
        ->and($reloadedTarget?->getFieldByHandle('email')->label)->toBe('Updated Email Label');
});

it('persists synced field label changes when the same definition appears twice in one form', function (): void {
    $sourceForm = formie()
        ->form([
            'title' => 'Sync Label Nested Source',
            'handle' => syncedFieldLabelSaveHandle('syncLabelNestedSource'),
        ])
        ->singleLineTextField('sharedText', ['label' => 'Shared Text'])
        ->create();

    $sourceField = $sourceForm->getFieldByHandle('sharedText');
    $syncedFieldConfig = syncedFieldLabelSaveStripImportedMeta($sourceField->getFormBuilderConfig());
    $syncedFieldConfig['type'] = SingleLineText::class;
    $syncedFieldConfig['fieldId'] = $sourceField->fieldId;
    $syncedFieldConfig['syncId'] = $sourceField->fieldId;
    $syncedFieldConfig['isSynced'] = true;

    $form = formie()
        ->form([
            'title' => 'Sync Label Nested Form',
            'handle' => syncedFieldLabelSaveHandle('syncLabelNestedForm'),
        ])
        ->groupField('deliveryAddress', [
            'label' => 'Delivery Address',
            'rows' => [[
                'fields' => [$syncedFieldConfig],
            ]],
        ])
        ->groupField('invoiceAddress', [
            'label' => 'Invoice Address',
            'rows' => [[
                'fields' => [array_merge($syncedFieldConfig)],
            ]],
        ])
        ->create();

    $reloadedForm = Form::find()->id($form->id)->one();
    $pages = $reloadedForm->getFormLayout()->getFormBuilderConfig();
    $pages[0]['rows'][0]['fields'][0]['rows'][0]['fields'][0]['label'] = 'Renamed Shared Text';

    $reloadedForm->getFormLayout()->setPages($pages);

    expect(Craft::$app->elements->saveElement($reloadedForm))->toBeTrue();

    $reloadedForm = Form::find()->id($form->id)->one();
    $deliveryField = $reloadedForm?->getFieldByHandle('deliveryAddress')?->getFieldByHandle('sharedText');
    $invoiceField = $reloadedForm?->getFieldByHandle('invoiceAddress')?->getFieldByHandle('sharedText');

    expect($deliveryField?->label)->toBe('Renamed Shared Text')
        ->and($invoiceField?->label)->toBe('Renamed Shared Text');
});

function syncedFieldLabelSaveStripImportedMeta(mixed $value): mixed
{
    $metaKeys = [
        'id' => true,
        'fieldId' => true,
        'layoutId' => true,
        'pageId' => true,
        'rowId' => true,
        'sortOrder' => true,
        'uid' => true,
        'reference' => true,
        'nestedLayoutId' => true,
    ];

    if (!is_array($value)) {
        return $value;
    }

    $stripped = [];

    foreach ($value as $key => $entryValue) {
        if (isset($metaKeys[$key])) {
            continue;
        }

        $stripped[$key] = syncedFieldLabelSaveStripImportedMeta($entryValue);
    }

    return $stripped;
}
