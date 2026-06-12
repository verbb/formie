<?php

declare(strict_types=1);

use Craft;
use craft\elements\Asset;
use Tests\Support\UploadTestHelper;
use verbb\formie\fields\FileUpload;
use verbb\formie\Formie;
use verbb\formie\helpers\FileUploadRetentionHelper;

it('collects nested file upload fields with asset retention settings', function (): void {
    $rows = [[
        'fields' => [[
            'type' => FileUpload::class,
            'handle' => 'nestedUpload',
            'label' => 'Nested Upload',
            'assetDataRetention' => 'days',
            'assetDataRetentionValue' => '30',
        ]],
    ]];

    $form = formie()
        ->form(['title' => 'Asset Retention Field Collection'])
        ->fileUploadField('topUpload', [
            'assetDataRetention' => 'days',
            'assetDataRetentionValue' => '7',
        ])
        ->groupField('groupUpload', ['rows' => $rows])
        ->create();

    $fields = FileUploadRetentionHelper::collectFieldsWithAssetRetention($form);

    expect($fields)->toHaveCount(2)
        ->and(array_map(static fn(FileUpload $field): string => $field->handle, $fields))
        ->toEqual(['topUpload', 'nestedUpload']);
});

it('purges uploaded assets for a field while keeping the submission', function (): void {
    $volume = UploadTestHelper::ensureUploadVolume();
    $form = formie()
        ->form(['title' => 'File Upload Asset Retention'])
        ->fileUploadField('documents', [
            'assetDataRetention' => 'days',
            'assetDataRetentionValue' => '1',
        ])
        ->create();

    $asset = UploadTestHelper::seedAsset('retention-doc.txt', 'retention', $volume);
    $submission = formie()
        ->submission($form)
        ->with(['documents' => [$asset->id]])
        ->save();

    Formie::$plugin->getFileUploads()->trackSubmissionAsset($asset, (int)$form->id, (int)$submission->id, $form->getFieldByHandle('documents')->uid);
    Formie::$plugin->getFileUploads()->finalizeSubmissionUploads((int)$submission->id);

    Craft::$app->getDb()->createCommand()
        ->update(Craft::$app->getDb()->quoteTableName('{{%elements}}'), [
            'dateCreated' => '2000-01-01 00:00:00',
        ], ['id' => (int)$submission->id])
        ->execute();

    $purged = Formie::$plugin->getFileUploads()->pruneExpiredFieldAssets();

    expect($purged)->toBe(1)
        ->and(Asset::find()->id($asset->id)->status(null)->one())->toBeNull();

    $reloaded = formie()->submission($form, (int)$submission->id);

    expect($reloaded)->not->toBeNull()
        ->and($reloaded->getFieldValue('documents')->ids())->toBe([]);
});

it('purges repeater file upload assets independently of the submission record', function (): void {
    $volume = UploadTestHelper::ensureUploadVolume();
    $rows = [[
        'fields' => [[
            'type' => FileUpload::class,
            'handle' => 'rowUpload',
            'label' => 'Row Upload',
            'restrictFiles' => false,
            'allowedKinds' => ['text'],
            'assetDataRetention' => 'days',
            'assetDataRetentionValue' => '1',
        ]],
    ]];

    $form = formie()
        ->form(['title' => 'Repeater File Upload Asset Retention'])
        ->repeaterField('repeatUpload', ['rows' => $rows])
        ->create();

    $asset = UploadTestHelper::seedAsset('repeater-retention.txt', 'repeater', $volume);
    $submission = formie()
        ->submission($form)
        ->with([
            'repeatUpload' => [[
                'rowUpload' => [$asset->id],
            ]],
        ])
        ->save();

    Craft::$app->getDb()->createCommand()
        ->update(Craft::$app->getDb()->quoteTableName('{{%elements}}'), [
            'dateCreated' => '2000-01-01 00:00:00',
        ], ['id' => (int)$submission->id])
        ->execute();

    $purged = Formie::$plugin->getFileUploads()->pruneExpiredFieldAssets();

    expect($purged)->toBe(1)
        ->and(Asset::find()->id($asset->id)->status(null)->one())->toBeNull()
        ->and(formie()->submission($form, (int)$submission->id)?->getFieldValue('repeatUpload.0.rowUpload')->ids())->toBe([]);
});

it('resolves nested file upload fields from submission content keys', function (): void {
    $rows = [[
        'fields' => [[
            'type' => FileUpload::class,
            'handle' => 'nestedUpload',
            'label' => 'Nested Upload',
        ]],
    ]];

    $form = formie()
        ->form(['title' => 'Resolve File Upload Content Keys'])
        ->groupField('groupUpload', ['rows' => $rows])
        ->create();

    $field = FileUploadRetentionHelper::resolveFileUploadFieldForContentKey($form, 'groupUpload.nestedUpload');

    expect($field)->not->toBeNull()
        ->and($field->handle)->toBe('nestedUpload');
});
