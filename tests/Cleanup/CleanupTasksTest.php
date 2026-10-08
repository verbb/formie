<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\services\Cleanup;

it('does not purge pending uploads when incomplete submission age is disabled', function (): void {
    $settings = Formie::$plugin->getSettings();
    $settings->maxIncompleteSubmissionAge = 0;

    $form = formie()->form()->singleLineTextField('name')->create();
    $asset = \Tests\Support\UploadTestHelper::seedAsset('disabled-retention.txt', 'keep');
    Formie::$plugin->getFileUploads()->trackSubmissionAsset($asset, (int)$form->id, null, 'retention');
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_PENDING_UPLOADS,
        ['dateUpdated' => '2000-01-01 00:00:00'], ['assetId' => $asset->id])->execute();
    try {
        Formie::$plugin->getFileUploads()->purgeStalePendingUploads();
        expect(\craft\elements\Asset::find()->id($asset->id)->status(null)->one())->not->toBeNull()
            ->and(Formie::$plugin->getFileUploads()->getTrackedUploadByAssetId($asset->id))->not->toBeNull();
    } finally {
        // The stale fixture must not become another test's purge candidate.
        Craft::$app->getElements()->deleteElement($asset, true);
    }
})->group('cleanup');

it('prunes expired canonical progress rows', function (): void {
    $form = formie()->form()->create();
    $service = Formie::$plugin->getSubmissionProgress();
    $expired = $service->upsertPageState($form);
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_SUBMISSION_PROGRESS, ['expiresAt' => time() - 1], ['id' => $expired->id])->execute();
    $form->setDraftContext('active');
    $active = $service->upsertPageState($form);
    expect($service->pruneProgress())->toBeGreaterThanOrEqual(1)
        ->and($service->loadProgress($expired->id))->toBeNull()
        ->and($service->loadProgress($active->id))->not->toBeNull();
})->group('cleanup');

it('exposes every cleanup task handle through the cleanup service', function (): void {
    expect(Cleanup::taskHandles())->toBe([
        Cleanup::TASK_PAYMENT_CAPABILITIES,
        Cleanup::TASK_SUBMISSION_OPERATIONS,
        Cleanup::TASK_INCOMPLETE_SUBMISSIONS,
        Cleanup::TASK_DATA_RETENTION_SUBMISSIONS,
        Cleanup::TASK_SENT_NOTIFICATIONS,
        Cleanup::TASK_FILE_UPLOAD_ASSET_RETENTION,
        Cleanup::TASK_STALE_PENDING_UPLOADS,
        Cleanup::TASK_REPORT_EXPORTS,
        Cleanup::TASK_SUBMISSION_GRANTS,
        Cleanup::TASK_SUBMISSION_PROGRESS,
    ]);
})->group('cleanup');
