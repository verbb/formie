<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\SingleLineText;
use verbb\formie\Formie;

it('allows the same synced field handle inside multiple group fields', function (): void {
    $sourceForm = formie()
        ->form(['title' => 'Nested Sync Source'])
        ->singleLineTextField('addressComponent', ['label' => 'Address Component'])
        ->create();

    $sourceField = $sourceForm->getFieldByHandle('addressComponent');
    $syncedFieldConfig = nestedGroupSyncedFieldsStripImportedMeta($sourceField->getFormBuilderConfig());
    $syncedFieldConfig['type'] = SingleLineText::class;
    $syncedFieldConfig['fieldId'] = $sourceField->fieldId;
    $syncedFieldConfig['syncId'] = $sourceField->fieldId;
    $syncedFieldConfig['isSynced'] = true;

    $targetForm = formie()
        ->form(['title' => 'Nested Sync Target'])
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

    $deliveryGroup = $targetForm->getFieldByHandle('deliveryAddress');
    $invoiceGroup = $targetForm->getFieldByHandle('invoiceAddress');
    $deliveryField = $deliveryGroup?->getFieldByHandle('addressComponent');
    $invoiceField = $invoiceGroup?->getFieldByHandle('addressComponent');

    expect($deliveryField)->not->toBeNull()
        ->and($invoiceField)->not->toBeNull()
        ->and($deliveryField->fieldId)->toBe($sourceField->fieldId)
        ->and($invoiceField->fieldId)->toBe($sourceField->fieldId)
        ->and($deliveryField->isSynced)->toBeTrue()
        ->and($invoiceField->isSynced)->toBeTrue();

    $submission = formie()
        ->submission($targetForm)
        ->with([
            'deliveryAddress' => ['addressComponent' => '123 Delivery St'],
            'invoiceAddress' => ['addressComponent' => '456 Invoice St'],
        ])
        ->save();

    expect($submission->getFieldValue('deliveryAddress.addressComponent'))->toBe('123 Delivery St')
        ->and($submission->getFieldValue('invoiceAddress.addressComponent'))->toBe('456 Invoice St');
});

it('keeps remaining nested synced group fields readable after removing one instance', function (): void {
    $sourceForm = formie()
        ->form(['title' => 'Nested Sync Delete Source'])
        ->singleLineTextField('addressComponent', ['label' => 'Address Component'])
        ->create();

    $sourceField = $sourceForm->getFieldByHandle('addressComponent');
    $syncedFieldConfig = nestedGroupSyncedFieldsStripImportedMeta($sourceField->getFormBuilderConfig());
    $syncedFieldConfig['type'] = SingleLineText::class;
    $syncedFieldConfig['fieldId'] = $sourceField->fieldId;
    $syncedFieldConfig['syncId'] = $sourceField->fieldId;
    $syncedFieldConfig['isSynced'] = true;

    $targetForm = formie()
        ->form(['title' => 'Nested Sync Delete Target'])
        ->groupField('deliveryAddress', [
            'rows' => [[
                'fields' => [$syncedFieldConfig],
            ]],
        ])
        ->groupField('invoiceAddress', [
            'rows' => [[
                'fields' => [array_merge($syncedFieldConfig)],
            ]],
        ])
        ->create();

    $deliveryField = $targetForm->getFieldByHandle('deliveryAddress')?->getFieldByHandle('addressComponent');
    $invoiceField = $targetForm->getFieldByHandle('invoiceAddress')?->getFieldByHandle('addressComponent');

    $submission = formie()
        ->submission($targetForm)
        ->with([
            'deliveryAddress' => ['addressComponent' => 'Keep me'],
            'invoiceAddress' => ['addressComponent' => 'Remove me'],
        ])
        ->save();

    Formie::$plugin->getFields()->deleteField($deliveryField);

    $reloadedForm = Form::find()->id($targetForm->id)->one();
    $reloadedInvoiceField = $reloadedForm?->getFieldByHandle('invoiceAddress')?->getFieldByHandle('addressComponent');
    $reloadedSubmission = Submission::find()->id($submission->id)->status(null)->one();

    expect($reloadedInvoiceField)->not->toBeNull()
        ->and($reloadedInvoiceField?->fieldId)->toBe($sourceField->fieldId)
        ->and($reloadedSubmission?->getFieldValue('invoiceAddress.addressComponent'))->toBe('Remove me');
});

function nestedGroupSyncedFieldsStripImportedMeta(mixed $value): mixed
{
    $metaKeys = [
        'id' => true,
        'fieldId' => true,
        'layoutId' => true,
        'pageId' => true,
        'rowId' => true,
        'uid' => true,
        'syncId' => true,
        'nestedLayoutId' => true,
        'contentTable' => true,
        'errors' => true,
        'settings' => true,
        'isSynced' => true,
        'reference' => true,
    ];

    if (!is_array($value)) {
        return $value;
    }

    $stripped = [];

    foreach ($value as $key => $entryValue) {
        if (is_string($key) && isset($metaKeys[$key])) {
            continue;
        }

        $stripped[$key] = nestedGroupSyncedFieldsStripImportedMeta($entryValue);
    }

    return $stripped;
}
