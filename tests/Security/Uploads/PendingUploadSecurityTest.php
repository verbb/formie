<?php

declare(strict_types=1);

use Tests\Support\UploadTestHelper;
use Craft;
use craft\db\Query;
use craft\elements\Asset;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;

it('returns pending upload metadata only for tracked asset ids', function (): void {
    $volume = UploadTestHelper::ensureUploadVolume();
    $form = formie()
        ->form(['title' => 'Pending Upload Metadata Security'])
        ->singleLineTextField('fullName')
        ->create();

    $trackedAsset = UploadTestHelper::seedAsset('tracked-upload.txt', 'tracked', $volume);
    $untrackedAsset = UploadTestHelper::seedAsset('untracked-upload.txt', 'untracked', $volume);

    Formie::$plugin->getFileUploads()->trackSubmissionAsset($trackedAsset, (int)$form->id, null, 'field-uid-test');

    $metadata = Formie::$plugin->getFileUploads()->getUploadMetadata([
        $trackedAsset->id,
        $untrackedAsset->id,
    ]);

    expect($metadata)->toHaveCount(1)
        ->and((int)$metadata[0]['assetId'])->toBe((int)$trackedAsset->id)
        ->and((int)$metadata[0]['formId'])->toBe((int)$form->id)
        ->and($metadata[0]['fieldUid'])->toBe('field-uid-test');
})->group('security');

it('purges only stale non-finalized pending uploads', function (): void {
    $volume = UploadTestHelper::ensureUploadVolume();
    $form = formie()
        ->form(['title' => 'Pending Upload Purge Security'])
        ->singleLineTextField('fullName')
        ->create();
    $submission = formie()
        ->submission($form)
        ->with(['fullName' => 'Security Tester'])
        ->save();

    $staleAsset = UploadTestHelper::seedAsset('stale-upload.txt', 'stale', $volume);
    $finalizedAsset = UploadTestHelper::seedAsset('finalized-upload.txt', 'finalized', $volume);

    Formie::$plugin->getFileUploads()->trackSubmissionAsset($staleAsset, (int)$form->id, null, 'stale-field');
    Formie::$plugin->getFileUploads()->trackSubmissionAsset($finalizedAsset, (int)$form->id, (int)$submission->id, 'finalized-field');
    Formie::$plugin->getFileUploads()->finalizeSubmissionUploads((int)$submission->id);

    $oldDate = '2000-01-01 00:00:00';
    Craft::$app->getDb()->createCommand()
        ->update(Table::FORMIE_PENDING_UPLOADS, ['dateUpdated' => $oldDate], ['assetId' => (int)$staleAsset->id])
        ->execute();
    Craft::$app->getDb()->createCommand()
        ->update(Table::FORMIE_PENDING_UPLOADS, ['dateUpdated' => $oldDate], ['assetId' => (int)$finalizedAsset->id])
        ->execute();

    $purged = Formie::$plugin->getFileUploads()->purgeStalePendingUploads(strtotime('2001-01-01 00:00:00'));

    $remainingAssetIds = (new Query())
        ->select(['assetId'])
        ->from(Table::FORMIE_PENDING_UPLOADS)
        ->column();
    $remainingAssetIds = array_map('intval', $remainingAssetIds);

    expect($purged)->toBe(1)
        ->and($remainingAssetIds)->toContain((int)$finalizedAsset->id)
        ->and($remainingAssetIds)->not->toContain((int)$staleAsset->id)
        ->and(Asset::find()->id($staleAsset->id)->status(null)->one())->toBeNull()
        ->and(Asset::find()->id($finalizedAsset->id)->status(null)->one())->not->toBeNull();
})->group('security');
