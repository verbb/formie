<?php

declare(strict_types=1);

use verbb\formie\elements\Form;

function duplicateFriendlySourceHandle(): string
{
    $alphabet = str_split('abcdefghijklmnopqrstuvwxyz');

    foreach ($alphabet as $candidate) {
        if (Form::find()->handle($candidate)->status(null)->one() === null) {
            return $candidate;
        }
    }

    return 'a';
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
