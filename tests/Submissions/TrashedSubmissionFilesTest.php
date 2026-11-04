<?php

declare(strict_types=1);

use Tests\Support\UploadTestHelper;
use craft\db\Query;
use craft\elements\Asset;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m261104_080000_trashed_submission_files;
use verbb\formie\services\Cleanup;

function trashedFilesSubmission(string $action = 'delete'): array
{
    UploadTestHelper::ensureUploadVolume();
    $form = formie()->form(['fileUploadsAction' => $action])->fileUploadField('documents', ['restrictFiles' => false])->create();
    $uploaded = UploadTestHelper::seedAsset('trashed-uploaded-' . bin2hex(random_bytes(4)) . '.txt', 'uploaded');
    $chosen = UploadTestHelper::seedAsset('trashed-chosen-' . bin2hex(random_bytes(4)) . '.txt', 'chosen');
    $submission = formie()->submission($form)->with(['documents' => [$uploaded->id, $chosen->id]])->save();
    Formie::$plugin->getFileUploads()->trackSubmissionAsset($uploaded, (int)$form->id, (int)$submission->id, $form->getFieldByHandle('documents')->uid, $form, 'documents');

    return [$form, $submission, (int)$uploaded->id, (int)$chosen->id];
}

function trashedFilesExist(int $assetId): bool
{
    return Asset::find()->id($assetId)->status(null)->exists();
}

function trashedFilesRecord(int $submissionId): bool
{
    return (new Query())->from(Table::FORMIE_TRASHED_SUBMISSION_FILES)->where(['submissionId' => $submissionId])->exists();
}

function trashedFilesPurge(bool $wholeRun = false): void
{
    $gc = Craft::$app->getGc();
    $gc->deleteAllTrashed = true;

    try {
        if ($wholeRun) {
            $gc->run(true);
        } else {
            $gc->hardDeleteElements();
            Formie::$plugin->getCleanup()->runTask(Cleanup::TASK_TRASHED_SUBMISSION_FILES);
        }
    } finally {
        $gc->deleteAllTrashed = false;
        UploadTestHelper::resetAssetFolderCaches();
    }
}

it('deletes files once garbage collection purges a trashed submission', function (): void {
    [, $submission, $uploaded, $chosen] = trashedFilesSubmission();

    expect(Craft::$app->getElements()->deleteElement($submission))->toBeTrue()
        ->and(trashedFilesExist($uploaded))->toBeTrue()
        ->and(trashedFilesExist($chosen))->toBeTrue();

    trashedFilesPurge(true);

    expect((new Query())->from('{{%elements}}')->where(['id' => $submission->id])->exists())->toBeFalse()
        ->and(trashedFilesExist($uploaded))->toBeFalse()
        ->and(Asset::find()->id($uploaded)->status(null)->trashed(null)->exists())->toBeFalse()
        ->and(trashedFilesExist($chosen))->toBeFalse()
        ->and(trashedFilesRecord((int)$submission->id))->toBeFalse();
});

it('leaves files alone while the submission is still in the trash', function (): void {
    [, $submission, $uploaded, $chosen] = trashedFilesSubmission();

    Craft::$app->getElements()->deleteElement($submission);
    Formie::$plugin->getCleanup()->runTask(Cleanup::TASK_TRASHED_SUBMISSION_FILES);

    expect(trashedFilesExist($uploaded))->toBeTrue()
        ->and(trashedFilesExist($chosen))->toBeTrue()
        ->and(trashedFilesRecord((int)$submission->id))->toBeTrue();
});

it('keeps files when the submission is restored before it is purged', function (): void {
    [, $submission, $uploaded, $chosen] = trashedFilesSubmission();

    Craft::$app->getElements()->deleteElement($submission);
    $trashed = Submission::find()->id($submission->id)->status(null)->trashed(true)->one();

    expect(Craft::$app->getElements()->restoreElement($trashed))->toBeTrue()
        ->and(trashedFilesRecord((int)$submission->id))->toBeFalse();

    trashedFilesPurge();

    expect(trashedFilesExist($uploaded))->toBeTrue()
        ->and(trashedFilesExist($chosen))->toBeTrue();
});

it('keeps files when the form retains them', function (): void {
    [, $submission, $uploaded, $chosen] = trashedFilesSubmission('retain');

    Craft::$app->getElements()->deleteElement($submission);

    expect(trashedFilesRecord((int)$submission->id))->toBeFalse();

    trashedFilesPurge();

    expect(trashedFilesExist($uploaded))->toBeTrue()
        ->and(trashedFilesExist($chosen))->toBeTrue();
});

it('deletes files once when a trashed submission is deleted permanently', function (): void {
    [, $submission, $uploaded, $chosen] = trashedFilesSubmission();

    Craft::$app->getElements()->deleteElement($submission);
    $trashed = Submission::find()->id($submission->id)->status(null)->trashed(true)->one();

    expect(Craft::$app->getElements()->deleteElement($trashed, true))->toBeTrue()
        ->and(trashedFilesExist($uploaded))->toBeFalse()
        ->and(trashedFilesExist($chosen))->toBeFalse()
        ->and(trashedFilesRecord((int)$submission->id))->toBeFalse();
});

it('keeps an uploaded file another submission still uses until that one goes too', function (): void {
    [$form, $submission, $uploaded] = trashedFilesSubmission();
    $other = formie()->submission($form)->with(['documents' => [$uploaded]])->save();

    Craft::$app->getElements()->deleteElement($submission);
    trashedFilesPurge();

    expect(trashedFilesExist($uploaded))->toBeTrue();

    expect(Craft::$app->getElements()->deleteElement($other, true))->toBeTrue();
    Formie::$plugin->getFileUploads()->purgeStalePendingUploads();

    expect(trashedFilesExist($uploaded))->toBeFalse();
});

it('deletes files when a trashed form and its submissions are purged', function (): void {
    [$form, $submission, $uploaded, $chosen] = trashedFilesSubmission();

    expect(Craft::$app->getElements()->deleteElement($form))->toBeTrue()
        ->and(trashedFilesRecord((int)$submission->id))->toBeTrue();

    trashedFilesPurge();

    expect(trashedFilesExist($uploaded))->toBeFalse()
        ->and(trashedFilesExist($chosen))->toBeFalse();
});

it('keeps files when a trashed form is restored', function (): void {
    [$form, $submission, $uploaded, $chosen] = trashedFilesSubmission();

    Craft::$app->getElements()->deleteElement($form);
    $trashed = Form::find()->id($form->id)->status(null)->trashed(true)->one();

    expect(Craft::$app->getElements()->restoreElement($trashed))->toBeTrue()
        ->and(trashedFilesRecord((int)$submission->id))->toBeFalse();

    trashedFilesPurge();

    expect(trashedFilesExist($uploaded))->toBeTrue()
        ->and(trashedFilesExist($chosen))->toBeTrue();
});

it('remembers the files of submissions already in the trash when upgrading', function (): void {
    [, $trashed, $uploaded, $chosen] = trashedFilesSubmission();
    [, $live, $liveUploaded] = trashedFilesSubmission();
    [, $retained, $retainedUploaded] = trashedFilesSubmission('retain');

    Craft::$app->getElements()->deleteElement($trashed);
    Craft::$app->getElements()->deleteElement($retained);
    Craft::$app->getDb()->createCommand()->delete(Table::FORMIE_TRASHED_SUBMISSION_FILES)->execute();

    $migration = new m261104_080000_trashed_submission_files();
    $migration->rememberTrashedSubmissions();

    expect(trashedFilesRecord((int)$trashed->id))->toBeTrue()
        ->and(trashedFilesRecord((int)$live->id))->toBeFalse()
        ->and(trashedFilesRecord((int)$retained->id))->toBeFalse();

    $row = (new Query())->from(Table::FORMIE_TRASHED_SUBMISSION_FILES)->where(['submissionId' => $trashed->id])->one();
    $migration->rememberTrashedSubmissions();

    expect((new Query())->from(Table::FORMIE_TRASHED_SUBMISSION_FILES)->where(['submissionId' => $trashed->id])->count())->toEqual(1)
        ->and((new Query())->from(Table::FORMIE_TRASHED_SUBMISSION_FILES)->where(['submissionId' => $trashed->id])->one()['chosenAssetIds'])->toBe($row['chosenAssetIds']);

    trashedFilesPurge();

    expect(trashedFilesExist($uploaded))->toBeFalse()
        ->and(trashedFilesExist($chosen))->toBeFalse()
        ->and(trashedFilesExist($liveUploaded))->toBeTrue()
        ->and(trashedFilesExist($retainedUploaded))->toBeTrue();
});
