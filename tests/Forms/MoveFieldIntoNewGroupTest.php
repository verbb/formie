<?php

declare(strict_types=1);

use verbb\formie\base\ParentFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\fields\Group;
use verbb\formie\fields\SingleLineText;
use verbb\formie\Formie;
use verbb\formie\helpers\FormSerializer;
use verbb\formie\helpers\StringHelper;

// Builder saves always send the whole layout. Moving an existing field into a Group added in the
// same edit sends that field with its persisted ID inside a Group that has no ID yet.
function moveFieldIntoNewGroupIdentities(Form $form, string $handle, ?string $group = null): array
{
    $field = $group ? $form->getFieldByHandle($group)->getFieldByHandle($handle) : $form->getFieldByHandle($handle);
    $identity = static fn($field) => [$field->id, $field->uid, $field->reference, $field->definitionId];
    $children = $field instanceof ParentFieldInterface ? array_map($identity, $field->getFields()) : [];

    return [$identity($field), $children];
}

function moveFieldIntoNewGroupSave(Form $form, array $pages): Form
{
    $form->getFormLayout()->setPages((new FormSerializer())->hydrateBuilder($pages, $form));
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue(json_encode($form->getErrors()));
    Formie::$plugin->getFields()->resetFieldRegistryCache();

    return Form::find()->id($form->id)->one();
}

it('keeps a moved field and its sub-fields when moving it into a new group', function (string $handle): void {
    $form = formie()->form()
        ->nameField('fullName', ['useMultipleFields' => true])
        ->addressField('postal')
        ->dateField('when')
        ->singleLineTextField('note')
        ->create();

    // The builder persists the default Name and Address sub-fields on its first save.
    $form = moveFieldIntoNewGroupSave($form, $form->getFormLayout()->getFormBuilderConfig());
    $before = moveFieldIntoNewGroupIdentities($form, $handle);

    expect($before[0][0])->toBeInt();

    if ($handle !== 'note') {
        expect($before[1])->not->toBe([]);
    }

    $pages = $form->getFormLayout()->getFormBuilderConfig();
    $moved = null;

    foreach ($pages[0]['rows'] as $rowIndex => $row) {
        foreach ($row['fields'] as $fieldIndex => $field) {
            if ($field['handle'] === $handle) {
                $moved = $field;
                array_splice($pages[0]['rows'][$rowIndex]['fields'], $fieldIndex, 1);
            }
        }
    }

    $pages[0]['rows'] = array_values(array_filter($pages[0]['rows'], fn($row) => $row['fields'] !== []));
    $pages[0]['rows'][] = ['fields' => [[
        'type' => Group::class,
        'id' => null,
        'label' => 'Group 1',
        'handle' => 'group1',
        'reference' => StringHelper::UUID(),
        'rows' => [['fields' => [$moved]]],
    ]]];

    $form = moveFieldIntoNewGroupSave($form, $pages);

    expect($form->getFieldByHandle($handle))->toBeNull()
        ->and(moveFieldIntoNewGroupIdentities($form, $handle, 'group1'))->toBe($before);

    // A later unrelated edit keeps the same instances in place.
    $form = moveFieldIntoNewGroupSave($form, $form->getFormLayout()->getFormBuilderConfig());

    expect(moveFieldIntoNewGroupIdentities($form, $handle, 'group1'))->toBe($before);
})->with([
    'name' => ['fullName'],
    'address' => ['postal'],
    'date' => ['when'],
    'single-line text' => ['note'],
]);

it('still gives a new group with new fields its own identities', function (): void {
    $form = formie()->form()->singleLineTextField('note')->create();
    $pages = $form->getFormLayout()->getFormBuilderConfig();
    $pages[0]['rows'][] = ['fields' => [[
        'type' => Group::class,
        'id' => null,
        'label' => 'Group 1',
        'handle' => 'group1',
        'reference' => StringHelper::UUID(),
        'rows' => [['fields' => [[
            'type' => SingleLineText::class,
            'id' => null,
            'label' => 'Inner',
            'handle' => 'inner',
            'reference' => StringHelper::UUID(),
        ]]]],
    ]]];

    $form = moveFieldIntoNewGroupSave($form, $pages);
    $inner = $form->getFieldByHandle('group1')->getFieldByHandle('inner');

    expect($inner)->toBeInstanceOf(SingleLineText::class)
        ->and($inner->id)->toBeInt()
        ->and($form->getFieldByHandle('note')->id)->not->toBe($inner->id);
});
