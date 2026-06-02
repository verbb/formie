<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Name;

it('keeps synced parent field nested uids stable when adding another synced instance', function (): void {
    $sourceForm = formie()
        ->form(['title' => 'Synced Parent Source'])
        ->nameField('identity', [
            'label' => 'Identity',
            'useMultipleFields' => true,
            'rows' => (new Name(['useMultipleFields' => true]))->getSubFields(),
        ])
        ->create();

    $sourceField = $sourceForm->getFieldByHandle('identity');
    $sourceNestedLayoutId = $sourceField?->nestedLayoutId;
    $sourceNestedUids = syncedParentRegressionNestedUids($sourceField);

    $submission = formie()
        ->submission($sourceForm)
        ->with([
            'identity' => [
                'firstName' => 'Ada',
                'lastName' => 'Lovelace',
            ],
        ])
        ->save();

    $syncedFieldConfig = syncedParentRegressionStripImportedMeta($sourceField->getFormBuilderConfig());
    $syncedFieldConfig['fieldId'] = $sourceField->fieldId;
    $syncedFieldConfig['syncId'] = $sourceField->fieldId;
    $syncedFieldConfig['isSynced'] = true;

    formie()
        ->form(['title' => 'Synced Parent Target'])
        ->addFieldConfig($syncedFieldConfig)
        ->create();

    $reloadedSourceForm = Form::find()->id($sourceForm->id)->one();
    $reloadedSourceField = $reloadedSourceForm?->getFieldByHandle('identity');
    $reloadedSubmission = Submission::find()->id($submission->id)->status(null)->one();

    expect($reloadedSourceField?->nestedLayoutId)->toBe($sourceNestedLayoutId)
        ->and(syncedParentRegressionNestedUids($reloadedSourceField))->toBe($sourceNestedUids)
        ->and($reloadedSubmission?->getFieldValue('identity.firstName'))->toBe('Ada')
        ->and($reloadedSubmission?->getFieldValue('identity.lastName'))->toBe('Lovelace');
});

function syncedParentRegressionStripImportedMeta(mixed $value): mixed
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

        $stripped[$key] = syncedParentRegressionStripImportedMeta($entryValue);
    }

    return $stripped;
}

function syncedParentRegressionNestedUids(mixed $field): array
{
    if (!$field instanceof Name) {
        return [];
    }

    $uids = [];

    foreach ($field->getFields() as $nestedField) {
        $uids[$nestedField->handle] = $nestedField->uid;
    }

    ksort($uids);

    return $uids;
}
