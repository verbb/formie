<?php

declare(strict_types=1);

use craft\elements\Asset;
use Tests\Support\UploadTestHelper;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;

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

    \verbb\formie\Formie::$plugin->getFileUploads()->trackSubmissionAsset($asset, (int)$form->id, (int)$submission->id, $form->getFieldByHandle('documents')->uid, $form, 'documents');

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

it('retains a deleted submission upload that another submission relates to', function (): void {
    $volume = UploadTestHelper::ensureUploadVolume();
    $uploads = Formie::$plugin->getFileUploads();

    $form = formie()
        ->form([
            'title' => 'File Upload Related Delete',
            'fileUploadsAction' => 'delete',
        ])
        ->fileUploadField('documents', ['restrictFiles' => false, 'allowedKinds' => ['text']])
        ->create();

    $relationForm = formie()
        ->form(['title' => 'File Upload Relation'])
        ->singleLineTextField('message')
        ->create();

    $asset = UploadTestHelper::seedAsset('related-upload.txt', 'keep me', $volume);
    $assetId = (int)$asset->id;

    $submission = formie()
        ->submission($form)
        ->with(['documents' => [$assetId]])
        ->save();

    $uploads->trackSubmissionAsset($asset, (int)$form->id, (int)$submission->id, $form->getFieldByHandle('documents')->uid, $form, 'documents');

    // Nothing else references the upload yet, so a positive result below comes from the relation.
    expect($uploads->isReferenced($assetId, (int)$submission->id, 'documents'))->toBeFalse();

    // Relate the upload to another submission through a posted `form.setRelations()` token.
    $relationForm->setRelations([$asset]);
    $token = $relationForm->getRelations();

    $related = formie()
        ->submission($relationForm)
        ->with(['message' => 'Related'])
        ->save();

    WebRequestTestHelper::withWebRequestContext(function () use ($related): void {
        Formie::$plugin->getRelations()->saveRelations($related);
    }, ['method' => 'POST', 'bodyParams' => ['relations' => $token]]);

    $relatedIds = static fn(): array => array_map(static fn($element): int => (int)$element->id, Formie::$plugin->getRelations()->getRelations($related));

    expect($relatedIds())->toBe([$assetId])
        ->and($uploads->isReferenced($assetId, (int)$submission->id, 'documents'))->toBeTrue();

    expect(\Craft::$app->elements->deleteElement($submission, true))->toBeTrue();

    $retained = Asset::find()->id($assetId)->one();

    expect($retained)->not->toBeNull()
        ->and($retained->getVolume()->fileExists($retained->getPath()))->toBeTrue()
        ->and($relatedIds())->toBe([$assetId])
        ->and($uploads->isReferenced($assetId))->toBeTrue()
        // A submission's own relation is not a reason to keep the file from that submission.
        ->and($uploads->isReferenced($assetId, (int)$related->id))->toBeFalse();

    \Craft::$app->elements->deleteElement($related, true);
    \Craft::$app->elements->deleteElement($retained, true);
});
