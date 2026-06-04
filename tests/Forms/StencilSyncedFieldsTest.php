<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use craft\helpers\StringHelper;
use verbb\formie\fields\Email;
use verbb\formie\models\Stencil;
use verbb\formie\models\StencilData;
use verbb\formie\services\Stencils as StencilsService;

function stencilSyncedFieldsHandle(string $prefix): string
{
    static $counter = 0;

    return $prefix . (++$counter) . uniqid();
}

it('stores synced definition metadata in stencil snapshots', function (): void {
    $sourceForm = formie()
        ->form(['title' => 'Stencil Sync Source', 'handle' => stencilSyncedFieldsHandle('syncSource')])
        ->emailField('email', ['label' => 'Email'])
        ->create();

    $sourceField = $sourceForm->getFieldByHandle('email');
    $syncedFieldConfig = $sourceField->getFormBuilderConfig();
    $syncedFieldConfig['fieldId'] = $sourceField->fieldId;
    $syncedFieldConfig['syncId'] = $sourceField->fieldId;
    $syncedFieldConfig['isSynced'] = true;

    $stencilSourceForm = formie()
        ->form(['title' => 'Stencil Sync Blueprint', 'handle' => stencilSyncedFieldsHandle('syncBlueprint')])
        ->addFieldConfig($syncedFieldConfig)
        ->create();

    $stencil = new Stencil([
        'name' => 'Synced Email Stencil',
        'handle' => stencilSyncedFieldsHandle('syncStencil'),
        'scope' => StencilsService::SCOPE_SITE,
    ]);
    $stencil->data = new StencilData();
    $stencil->data->populateFormData($stencilSourceForm);

    $serializedField = $stencil->data->getSerializedData()['pages'][0]['rows'][0]['fields'][0] ?? null;

    expect($serializedField)->toBeArray()
        ->and($serializedField['syncedDefinitionHandle'] ?? null)->toBe('email')
        ->and((int)($serializedField['syncedDefinitionId'] ?? 0))->toBe($sourceField->fieldId);
});

it('materializes new forms with synced fields linked to the shared definition', function (): void {
    $sourceForm = formie()
        ->form(['title' => 'Stencil Materialize Source', 'handle' => stencilSyncedFieldsHandle('matSource')])
        ->emailField('email', ['label' => 'Email'])
        ->create();

    $sourceField = $sourceForm->getFieldByHandle('email');
    $definitionId = $sourceField->fieldId;

    $syncedFieldConfig = $sourceField->getFormBuilderConfig();
    $syncedFieldConfig['fieldId'] = $definitionId;
    $syncedFieldConfig['syncId'] = $definitionId;
    $syncedFieldConfig['isSynced'] = true;

    $stencilSourceForm = formie()
        ->form(['title' => 'Stencil Materialize Blueprint', 'handle' => stencilSyncedFieldsHandle('matBlueprint')])
        ->addFieldConfig($syncedFieldConfig)
        ->create();

    $stencil = new Stencil([
        'name' => 'Materialize Synced Stencil',
        'handle' => stencilSyncedFieldsHandle('matStencil'),
        'scope' => StencilsService::SCOPE_SITE,
    ]);
    $stencil->data = new StencilData();
    $stencil->data->populateFormData($stencilSourceForm);

    $newForm = new Form([
        'title' => 'Materialized Form',
        'handle' => stencilSyncedFieldsHandle('matForm'),
    ]);
    $stencil->applyStencilToForm($newForm, true);

    $materializedField = $newForm->getFieldByHandle('email');

    expect($materializedField)->not->toBeNull()
        ->and($materializedField->fieldId)->toBe($definitionId)
        ->and($materializedField->getIsSynced())->toBeTrue();

    expect(Craft::$app->elements->saveElement($newForm))->toBeTrue();

    $reloaded = Form::find()->id($newForm->id)->one();
    $reloadedField = $reloaded?->getFieldByHandle('email');

    expect($reloadedField?->fieldId)->toBe($definitionId);

    $usageCount = (int)(new craft\db\Query())
        ->from('{{%formie_form_fields}}')
        ->where(['fieldId' => $definitionId])
        ->count();

    expect($usageCount)->toBeGreaterThan(1);
});

it('resolves shared definitions by handle when materializing stencils', function (): void {
    $sourceForm = formie()
        ->form(['title' => 'Stencil Handle Resolve Source', 'handle' => stencilSyncedFieldsHandle('handleSource')])
        ->emailField('email', ['label' => 'Email'])
        ->create();

    $definitionId = $sourceForm->getFieldByHandle('email')->fieldId;

    $stencil = new Stencil([
        'name' => 'Handle Resolve Stencil',
        'handle' => stencilSyncedFieldsHandle('handleStencil'),
        'scope' => StencilsService::SCOPE_SITE,
    ]);
    $stencil->data = new StencilData([
        'pages' => [
            [
                'label' => 'Page 1',
                'settings' => [],
                'rows' => [
                    [
                        'fields' => [
                            [
                                'type' => Email::class,
                                'reference' => StringHelper::UUID(),
                                'syncedDefinitionHandle' => 'email',
                                'settings' => [
                                    'label' => 'Email',
                                    'handle' => 'email',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $newForm = new Form([
        'title' => 'Handle Resolved Form',
        'handle' => stencilSyncedFieldsHandle('handleForm'),
    ]);
    $stencil->applyStencilToForm($newForm, true);

    expect($newForm->getFieldByHandle('email')?->fieldId)->toBe($definitionId);
});
