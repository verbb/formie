<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;

it('supports submission delete and restore lifecycle', function (): void {
    $form = formie()
        ->form(['title' => 'Submission Restore'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()
        ->submission($form)
        ->with(['fullName' => 'Restore Me'])
        ->save();

    $deleted = \Craft::$app->elements->deleteElement($submission);
    $trashed = Submission::find()->id($submission->id)->trashed(true)->one();
    $restored = $trashed ? \Craft::$app->elements->restoreElement($trashed) : false;
    $reloaded = Submission::find()->id($submission->id)->one();

    expect($deleted)->toBeTrue()
        ->and($trashed)->not->toBeNull()
        ->and($restored)->toBeTrue()
        ->and($reloaded)->not->toBeNull();
});
