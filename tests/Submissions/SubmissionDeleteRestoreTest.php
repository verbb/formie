<?php

declare(strict_types=1);

use craft\elements\Asset;
use Tests\Support\UploadTestHelper;
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

it('retains file upload assets when trashing a submission configured to delete files', function (): void {
    $volume = UploadTestHelper::ensureUploadVolume();

    $form = formie()
        ->form([
            'title' => 'File Upload Trash Restore',
            'fileUploadsAction' => 'delete',
        ])
        ->fileUploadField('documents', ['restrictFiles' => false, 'allowedKinds' => ['text']])
        ->create();

    $asset = UploadTestHelper::seedAsset('restore-me.txt', 'keep me', $volume);
    $assetId = (int)$asset->id;

    $submission = formie()
        ->submission($form)
        ->with(['documents' => [$assetId]])
        ->save();

    $deleted = \Craft::$app->elements->deleteElement($submission);

    expect($deleted)->toBeTrue()
        ->and(Asset::find()->id($assetId)->one())->not->toBeNull();

    $trashed = Submission::find()->id($submission->id)->trashed(true)->one();
    $restored = $trashed ? \Craft::$app->elements->restoreElement($trashed) : false;
    $reloaded = Submission::find()->id($submission->id)->one();

    expect($restored)->toBeTrue()
        ->and($reloaded)->not->toBeNull()
        ->and($reloaded->getFieldValue('documents')->ids())->toBe([$assetId]);

    \Craft::$app->elements->deleteElement($reloaded, true);

    expect(Asset::find()->id($assetId)->one())->toBeNull();
});
