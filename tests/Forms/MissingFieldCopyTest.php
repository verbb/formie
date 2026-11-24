<?php

declare(strict_types=1);

use craft\db\Query;
use craft\helpers\Db;
use verbb\formie\elements\Form;
use verbb\formie\fields\MissingField;
use verbb\formie\helpers\ImportExportHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\Stencil;
use verbb\formie\models\StencilData;
use verbb\formie\services\Stencils as StencilsService;

/**
 * Copies of a form whose fields have an unavailable type (an uninstalled plugin's type, or a disabled type),
 * which load as Missing Fields. Each copy keeps the fields' own handles and labels.
 */
function missingFieldCopySource(array $handles): array
{
    $form = formie()->form(['title' => 'Missing Field Copy', 'handle' => 'missingFieldCopy' . uniqid()])
        ->singleLineTextField('accountNumber', ['label' => 'Account number'])
        ->singleLineTextField('companyName', ['label' => 'Company name'])
        ->numberField('amount', ['label' => 'Amount'])
        ->create();

    $form = missingFieldCopyReload($form->id);
    $types = [];

    foreach ($handles as $handle) {
        $definitionId = $form->getFieldByHandle($handle)->definitionId;
        $types[$definitionId] = (new Query())->select('type')->from(Table::FORMIE_FIELDS)->where(['id' => $definitionId])->scalar();
        Db::update(Table::FORMIE_FIELDS, ['type' => 'absent\\Field' . ucfirst($handle)], ['id' => $definitionId]);
    }

    return [missingFieldCopyReload($form->id), $types];
}

function missingFieldCopyRestore(array $types): void
{
    foreach ($types as $definitionId => $type) {
        Db::update(Table::FORMIE_FIELDS, ['type' => $type], ['id' => $definitionId]);
    }
}

function missingFieldCopyReload(int $id): Form
{
    Craft::$app->getElements()->invalidateAllCaches();
    \verbb\formie\Formie::$plugin->getFields()->resetFieldRegistryCache();

    return Form::find()->id($id)->status(null)->one();
}

function missingFieldCopyFields(Form $form): array
{
    return array_map(fn($field) => [
        'handle' => $field->handle,
        'label' => $field->label,
        'type' => $field instanceof MissingField ? $field->expectedType : get_class($field),
    ], $form->getFields());
}

function missingFieldCopyExpected(array $missing): array
{
    $fields = [
        'accountNumber' => ['Account number', \verbb\formie\fields\SingleLineText::class],
        'companyName' => ['Company name', \verbb\formie\fields\SingleLineText::class],
        'amount' => ['Amount', \verbb\formie\fields\Number::class],
    ];

    return array_map(fn($handle) => [
        'handle' => $handle,
        'label' => $fields[$handle][0],
        'type' => in_array($handle, $missing, true) ? 'absent\\Field' . ucfirst($handle) : $fields[$handle][1],
    ], array_keys($fields));
}

it('keeps the handles and labels of fields with an unavailable type when duplicating a form', function(array $missing): void {
    [$form, $types] = missingFieldCopySource($missing);

    try {
        $copy = Craft::$app->getElements()->duplicateElement($form, $form->getDuplicateAttributes());

        expect(missingFieldCopyFields(missingFieldCopyReload($copy->id)))->toBe(missingFieldCopyExpected($missing));
    } finally {
        missingFieldCopyRestore($types);
    }
})->with([
    'one field' => [['companyName']],
    'two fields' => [['accountNumber', 'companyName']],
]);

it('keeps the handles and labels of fields with an unavailable type in a stencil and the forms made from it', function(): void {
    [$form, $types] = missingFieldCopySource(['accountNumber', 'companyName']);

    try {
        $stencil = new Stencil(['name' => 'Missing Field Copy', 'handle' => 'missingFieldCopy' . uniqid(), 'scope' => StencilsService::SCOPE_SITE]);
        $stencil->data = new StencilData();
        $stencil->data->populateFormData($form);

        $stored = array_merge(...array_column($stencil->data->getSerializedData()['pages'][0]['rows'], 'fields'));
        expect(array_map(fn($field) => [$field['settings']['handle'] ?? null, $field['settings']['label'] ?? null], $stored))->toBe([
            ['accountNumber', 'Account number'],
            ['companyName', 'Company name'],
            ['amount', 'Amount'],
        ]);

        $created = new Form(['title' => 'From stencil', 'handle' => 'missingFieldCopyStencil' . uniqid()]);
        $stencil->applyStencilToForm($created, true);
        expect(Craft::$app->getElements()->saveElement($created))->toBeTrue();

        expect(missingFieldCopyFields(missingFieldCopyReload($created->id)))->toBe(missingFieldCopyExpected(['accountNumber', 'companyName']));
    } finally {
        missingFieldCopyRestore($types);
    }
});

it('keeps the handles and labels of fields with an unavailable type in a form export and its import', function(): void {
    [$form, $types] = missingFieldCopySource(['accountNumber', 'companyName']);

    try {
        $export = ImportExportHelper::generateFormExport($form);
        $exported = array_merge(...array_column($export['pages'][0]['rows'], 'fields'));

        expect(array_map(fn($field) => [$field['settings']['handle'] ?? null, $field['settings']['label'] ?? null], $exported))->toBe([
            ['accountNumber', 'Account number'],
            ['companyName', 'Company name'],
            ['amount', 'Amount'],
        ]);

        $export['handle'] = 'missingFieldCopyImport' . uniqid();
        $imported = ImportExportHelper::createFormFromImport($export);
        expect(Craft::$app->getElements()->saveElement($imported))->toBeTrue();

        expect(missingFieldCopyFields(missingFieldCopyReload($imported->id)))->toBe(missingFieldCopyExpected(['accountNumber', 'companyName']));
    } finally {
        missingFieldCopyRestore($types);
    }
});
