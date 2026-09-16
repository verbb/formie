<?php

declare(strict_types=1);

use Craft;
use craft\db\Query;
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
    $purged = Formie::$plugin->getFileUploads()->purgeStalePendingUploads();
    expect(\craft\elements\Asset::find()->id($asset->id)->status(null)->one())->not->toBeNull();

    expect($purged)->toBe(0);
    // The stale fixture must not become another test's purge candidate.
    Craft::$app->getElements()->deleteElement($asset, true);
})->group('cleanup');

it('prunes expired draft storage rows', function (): void {
    expect(Craft::$app->getDb()->tableExists(Table::FORMIE_SUBMISSION_DRAFTS))->toBeTrue();

    $now = gmdate('Y-m-d H:i:s');
    $expired = gmdate('Y-m-d H:i:s', strtotime('-2 days'));

    Craft::$app->getDb()->createCommand()
        ->insert(Table::FORMIE_SUBMISSION_DRAFTS, [
            'storageKey' => 'formie:test-expired-draft',
            'value' => '{"value":[]}',
            'dateExpires' => $expired,
            'dateCreated' => $expired,
            'dateUpdated' => $expired,
        ])
        ->execute();

    Craft::$app->getDb()->createCommand()
        ->insert(Table::FORMIE_SUBMISSION_DRAFTS, [
            'storageKey' => 'formie:test-active-draft',
            'value' => '{"value":[]}',
            'dateExpires' => gmdate('Y-m-d H:i:s', strtotime('+2 days')),
            'dateCreated' => $now,
            'dateUpdated' => $now,
        ])
        ->execute();

    $purged = Formie::$plugin->getSubmissionDrafts()->pruneExpiredDraftStorage();

    $remainingKeys = (new Query())
        ->select(['storageKey'])
        ->from(Table::FORMIE_SUBMISSION_DRAFTS)
        ->column();

    expect($purged)->toBeGreaterThanOrEqual(1)
        ->and($remainingKeys)->toContain('formie:test-active-draft')
        ->and($remainingKeys)->not->toContain('formie:test-expired-draft');
})->group('cleanup');

it('exposes every cleanup task handle through the cleanup service', function (): void {
    expect(Cleanup::taskHandles())->toBe([
        Cleanup::TASK_INCOMPLETE_SUBMISSIONS,
        Cleanup::TASK_DATA_RETENTION_SUBMISSIONS,
        Cleanup::TASK_SENT_NOTIFICATIONS,
        Cleanup::TASK_FILE_UPLOAD_ASSET_RETENTION,
        Cleanup::TASK_STALE_PENDING_UPLOADS,
        Cleanup::TASK_REPORT_EXPORTS,
        Cleanup::TASK_SUBMISSION_STATES,
        Cleanup::TASK_DRAFT_STORAGE,
    ]);
})->group('cleanup');
