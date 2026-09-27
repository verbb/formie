<?php

declare(strict_types=1);

use craft\elements\Asset;
use Tests\Support\UploadTestHelper;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use yii\base\Event;

beforeEach(fn() => UploadTestHelper::ensureUploadVolume());

it('retains failed staged deletions for expiry cleanup and retries them', function (string $mode): void {
    $form = formie()->form()->fileUploadField('documents', ['restrictFiles' => false])->create();
    $asset = UploadTestHelper::seedAsset('cleanup-retry.txt', 'retry');
    $uploads = Formie::$plugin->getFileUploads();
    $uploads->trackSubmissionAsset($asset, (int)$form->id, null, $form->getFieldByHandle('documents')->uid, $form, 'documents');
    $handler = static function ($event) use ($asset, $mode): void {
        if ((int)$event->sender->id !== (int)$asset->id) {
            return;
        }
        if ($mode === 'exception') {
            throw new RuntimeException('Controlled deletion failure.');
        }
        if ($mode === 'retained-file') {
            $event->sender->keepFileOnDelete = true;
            return;
        }
        $event->isValid = false;
    };
    Event::on(Asset::class, Asset::EVENT_BEFORE_DELETE, $handler);
    try {
        expect($uploads->removeUploadByAssetId((int)$asset->id))->toBeFalse();
        $row = $uploads->getTrackedUploadByAssetId((int)$asset->id);
        expect($row['state'])->toBe('rejected')->and($row['failureCode'])->toBe('deletionFailed');
        $uploads->purgeStalePendingUploads();
        $row = $uploads->getTrackedUploadByAssetId((int)$asset->id);
        expect($row['state'])->toBe('expired')->and($row['failureCode'])->toBe('deletionFailed')
            ->and(Asset::find()->id($asset->id)->one())->not->toBeNull();
    } finally {
        Event::off(Asset::class, Asset::EVENT_BEFORE_DELETE, $handler);
    }
    $uploads->purgeStalePendingUploads();
    expect($uploads->getTrackedUploadByAssetId((int)$asset->id))->toBeNull()
        ->and(Asset::find()->id($asset->id)->trashed(null)->status(null)->one())->toBeNull();
})->with(['declined', 'exception', 'retained-file']);

it('retains only failed owned files when field retention partially succeeds', function (): void {
    $form = formie()->form()->fileUploadField('documents', [
        'restrictFiles' => false, 'assetDataRetention' => 'days', 'assetDataRetentionValue' => '1',
    ])->create();
    $deleted = UploadTestHelper::seedAsset('cleanup-success.txt', 'success');
    $failed = UploadTestHelper::seedAsset('cleanup-failed.txt', 'failure');
    $foreign = UploadTestHelper::seedAsset('cleanup-not-owned.txt', 'not owned');
    $submission = formie()->submission($form)->with(['documents' => [$deleted->id, $failed->id, $foreign->id]])->save();
    $uploads = Formie::$plugin->getFileUploads();
    foreach ([$deleted, $failed] as $asset) {
        $uploads->trackSubmissionAsset($asset, (int)$form->id, (int)$submission->id, $form->getFieldByHandle('documents')->uid, $form, 'documents');
    }
    Craft::$app->getDb()->createCommand()->update('{{%elements}}', ['dateCreated' => '2000-01-01 00:00:00'], ['id' => $submission->id])->execute();
    $handler = static function ($event) use ($failed): void {
        if ((int)$event->sender->id === (int)$failed->id) {
            $event->isValid = false;
        }
    };
    Event::on(Asset::class, Asset::EVENT_BEFORE_DELETE, $handler);
    try {
        $uploads->pruneExpiredFieldAssets();
        $reloaded = Submission::find()->id($submission->id)->one();
        expect(array_map('intval', $reloaded->getFieldValue('documents')->ids()))->toBe([(int)$failed->id])
            ->and(Asset::find()->id($deleted->id)->one())->toBeNull()
            ->and(Asset::find()->id($foreign->id)->one())->not->toBeNull()
            ->and($uploads->getTrackedUploadByAssetId((int)$failed->id)['failureCode'])->toBe('deletionFailed');
    } finally {
        Event::off(Asset::class, Asset::EVENT_BEFORE_DELETE, $handler);
    }
    $uploads->pruneExpiredFieldAssets();
    expect(Submission::find()->id($submission->id)->one()->getFieldValue('documents')->ids())->toBe([])
        ->and($uploads->getTrackedUploadByAssetId((int)$failed->id))->toBeNull();
});

it('removes only the expired relation when another field still references the same file', function (): void {
    $form = formie()->form()
        ->fileUploadField('expires', ['restrictFiles' => false, 'assetDataRetention' => 'days', 'assetDataRetentionValue' => '1'])
        ->fileUploadField('retained', ['restrictFiles' => false])
        ->create();
    $asset = UploadTestHelper::seedAsset('cleanup-shared.txt', 'shared');
    $submission = formie()->submission($form)->with(['expires' => [$asset->id], 'retained' => [$asset->id]])->save();
    $uploads = Formie::$plugin->getFileUploads();
    $uploads->trackSubmissionAsset($asset, (int)$form->id, (int)$submission->id, $form->getFieldByHandle('expires')->uid, $form, 'expires');
    Craft::$app->getDb()->createCommand()->update('{{%elements}}', ['dateCreated' => '2000-01-01 00:00:00'], ['id' => $submission->id])->execute();
    $uploads->pruneExpiredFieldAssets();
    $reloaded = Submission::find()->id($submission->id)->one();
    expect($reloaded->getFieldValue('expires')->ids())->toBe([])
        ->and(array_map('intval', $reloaded->getFieldValue('retained')->ids()))->toBe([(int)$asset->id])
        ->and(Asset::find()->id($asset->id)->one())->not->toBeNull()
        ->and($uploads->getTrackedUploadByAssetId((int)$asset->id))->not->toBeNull();
});

it('preserves an asset referenced by a restorable trashed submission', function (): void {
    $form = formie()->form()->fileUploadField('documents', ['restrictFiles' => false])->create();
    $asset = UploadTestHelper::seedAsset('cleanup-trashed.txt', 'restorable');
    $submission = formie()->submission($form)->with(['documents' => [$asset->id]])->save();
    $uploads = Formie::$plugin->getFileUploads();
    $uploads->trackSubmissionAsset($asset, (int)$form->id, null, $form->getFieldByHandle('documents')->uid, $form, 'documents');
    expect(Craft::$app->getElements()->deleteElement($submission))->toBeTrue();
    expect($uploads->removeUploadByAssetId((int)$asset->id))->toBeFalse()
        ->and($uploads->getTrackedUploadByAssetId((int)$asset->id)['state'])->toBe('staged')
        ->and(Asset::find()->id($asset->id)->one())->not->toBeNull();
});

it('keeps retryable cleanup after the owning submission is permanently deleted', function (string $mode): void {
    $form = formie()->form(['fileUploadsAction' => 'delete'])->fileUploadField('documents', ['restrictFiles' => false])->create();
    $asset = UploadTestHelper::seedAsset('cleanup-deleted-submission.txt', 'retry after cascade');
    $unowned = UploadTestHelper::seedAsset('cleanup-unowned-submission.txt', 'keep');
    $submission = formie()->submission($form)->with(['documents' => [$asset->id, $unowned->id]])->save();
    $uploads = Formie::$plugin->getFileUploads();
    $uploads->trackSubmissionAsset($asset, (int)$form->id, (int)$submission->id, $form->getFieldByHandle('documents')->uid, $form, 'documents');
    $uid = $uploads->getTrackedUploadByAssetId((int)$asset->id)['uid'];
    $handler = static function ($event) use ($asset, $mode): void {
        if ((int)$event->sender->id !== (int)$asset->id) {
            return;
        }
        if ($mode === 'exception') {
            throw new RuntimeException('Controlled deletion failure.');
        }
        $event->isValid = false;
    };
    Event::on(Asset::class, Asset::EVENT_BEFORE_DELETE, $handler);
    try {
        expect(Craft::$app->getElements()->deleteElement($submission, true))->toBeTrue();
        $tracked = $uploads->getTrackedUploadByAssetId((int)$asset->id);
        expect($tracked['uid'])->toBe($uid)->and($tracked['submissionId'])->toBeNull()
            ->and($tracked['state'])->toBe('expired')->and($tracked['failureCode'])->toBe('deletionFailed')
            ->and(Submission::find()->id($submission->id)->trashed(null)->one())->toBeNull()
            ->and(Asset::find()->id($unowned->id)->one())->not->toBeNull();
    } finally {
        Event::off(Asset::class, Asset::EVENT_BEFORE_DELETE, $handler);
    }
    $uploads->purgeStalePendingUploads();
    expect($uploads->getTrackedUploadByAssetId((int)$asset->id))->toBeNull()
        ->and(Asset::find()->id($asset->id)->one())->toBeNull();
})->with(['declined', 'exception']);

it('retains shared owned files after deletion until the final reference is removed', function (): void {
    $form = formie()->form(['fileUploadsAction' => 'delete'])->fileUploadField('documents', ['restrictFiles' => false])->create();
    $asset = UploadTestHelper::seedAsset('cleanup-shared-deletion.txt', 'shared');
    $owner = formie()->submission($form)->with(['documents' => [$asset->id]])->save();
    $other = formie()->submission($form)->with(['documents' => [$asset->id]])->save();
    $uploads = Formie::$plugin->getFileUploads();
    $uploads->trackSubmissionAsset($asset, (int)$form->id, (int)$owner->id, $form->getFieldByHandle('documents')->uid, $form, 'documents');
    expect(Craft::$app->getElements()->deleteElement($owner, true))->toBeTrue();
    $uploads->purgeStalePendingUploads();
    expect(Asset::find()->id($asset->id)->one())->not->toBeNull()
        ->and($uploads->getTrackedUploadByAssetId((int)$asset->id)['state'])->toBe('expired');
    expect(Craft::$app->getElements()->deleteElement($other, true))->toBeTrue();
    $uploads->purgeStalePendingUploads();
    expect(Asset::find()->id($asset->id)->one())->toBeNull()
        ->and($uploads->getTrackedUploadByAssetId((int)$asset->id))->toBeNull();
});
