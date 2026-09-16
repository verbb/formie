<?php

declare(strict_types=1);

use verbb\formie\elements\Form;

function duplicateFriendlySourceHandle(): string
{
    return 'test' . bin2hex(random_bytes(8));
}

it('duplicates forms while preserving key settings and layout presence', function (): void {
    $form = formie()
        ->form(['title' => 'Duplicate Source', 'handle' => duplicateFriendlySourceHandle()])
        ->singleLineTextField('fullName')
        ->submitAction('message', ['message' => 'Saved'])
        ->create();

    $duplicate = Craft::$app->elements->duplicateElement($form, $form->getDuplicateAttributes());

    expect($duplicate)->not->toBeNull()
        ->and($duplicate->id)->not->toBe($form->id)
        ->and($duplicate->uid)->not->toBe($form->uid)
        ->and($duplicate->getFormLayout())->not->toBeNull()
        ->and($duplicate->settings->submitAction)->toBe('message');
});

it('supports delete and restore lifecycle for forms', function (): void {
    $form = formie()
        ->form(['title' => 'Delete Restore'])
        ->singleLineTextField('fullName')
        ->create();

    $deleted = Craft::$app->elements->deleteElement($form);
    $trashed = Form::find()->id($form->id)->trashed(true)->one();

    $restored = false;

    if ($trashed) {
        $restored = Craft::$app->elements->restoreElement($trashed);
    }

    $reloaded = Form::find()->id($form->id)->one();

    expect($deleted)->toBeTrue()
        ->and($trashed)->not->toBeNull()
        ->and($restored)->toBeTrue()
        ->and($reloaded)->not->toBeNull();
});

it('duplicates nested layouts without changing the source field identities or labels', function (): void {
    $source = formie()->form(['handle' => duplicateFriendlySourceHandle()])->groupField('contact', [
        'rows' => [['fields' => [[
            'type' => \verbb\formie\fields\SingleLineText::class,
            'handle' => 'name', 'label' => 'Original',
        ]]]],
    ])->create();
    $sourceField = $source->getFieldByHandle('contact')->getFieldByHandle('name');
    $sourceId = $sourceField->id;
    $sourceUid = $sourceField->uid;
    $duplicate = Craft::$app->getElements()->duplicateElement($source, $source->getDuplicateAttributes());
    $copiedField = $duplicate->getFieldByHandle('contact')->getFieldByHandle('name');
    expect($sourceField->id)->toBe($sourceId)->and($sourceField->uid)->toBe($sourceUid)
        ->and($copiedField)->not->toBe($sourceField)
        ->and($copiedField->uid)->not->toBe($sourceUid);
    $duplicate->getFieldByHandle('contact')->getFieldLayout()->getFieldByHandle('name')->label = 'Edited copy';
    expect(Craft::$app->getElements()->saveElement($duplicate))->toBeTrue();
    $reloaded = Form::find()->id($source->id)->one();
    expect($reloaded->getFieldByHandle('contact')->getFieldByHandle('name')->label)->toBe('Original');
    $reloadedCopy = Form::find()->id($duplicate->id)->one();
    expect($reloadedCopy->getFieldByHandle('contact')->getFieldByHandle('name')->label)->toBe('Edited copy');
});
