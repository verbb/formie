<?php

use craft\elements\Asset;
use craft\helpers\Assets;
use Tests\Support\UploadTestHelper;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use yii\web\BadRequestHttpException;

it('enforces an aggregate staging budget across fields including expired files', function (int $files, int $bytes): void {
    WebRequestTestHelper::withWebRequestContext(function () use ($files, $bytes): void {
        UploadTestHelper::ensureUploadVolume();
        $form = formie()->form()->fileUploadField('first')->fileUploadField('second')->create();
        $settings = Formie::$plugin->getSettings();
        $oldFiles = $settings->maxStagedUploadFiles;
        $oldBytes = $settings->maxStagedUploadBytes;
        $settings->maxStagedUploadFiles = $files;
        $settings->maxStagedUploadBytes = $bytes;
        $uploads = Formie::$plugin->getFileUploads();
        $folder = $uploads->getStagingFolder();
        $paths = [];
        $assets = [];
        try {
            foreach (['first', 'second', 'first'] as $index => $handle) {
                $filename = 'budget-' . uniqid() . '.txt';
                $path = Assets::tempFilePath($filename);
                file_put_contents($path, 'x');
                $paths[] = $path;
                $asset = new Asset(['tempFilePath' => $path, 'filename' => $filename, 'newFolderId' => $folder->id]);
                $asset->setVolumeId($folder->volumeId);
                $asset->setScenario(Asset::SCENARIO_CREATE);
                $save = fn() => $uploads->saveStagedAsset($asset, $form, null, $form->getFieldByHandle($handle)->uid, $handle);
                if ($index < 2) {
                    expect($save())->toBeTrue();
                    $assets[] = $asset;
                } else {
                    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_PENDING_UPLOADS, ['state' => 'expired', 'expiresAt' => time() - 1], ['assetId' => $assets[1]->id])->execute();
                    expect($save)->toThrow(BadRequestHttpException::class, 'staged upload budget')
                        ->and($asset->id)->toBeNull();
                    expect($uploads->removeUploadByAssetId((int)$assets[0]->id))->toBeTrue();
                    expect($save())->toBeTrue();
                }
            }
        } finally {
            $settings->maxStagedUploadFiles = $oldFiles;
            $settings->maxStagedUploadBytes = $oldBytes;
            foreach ($paths as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }
    });
})->with(['file count' => [2, 1024], 'byte count' => [50, 2]]);

it('bounds staged expiry independently of unlimited or long submission retention', function (int $retention, int $days): void {
    UploadTestHelper::ensureUploadVolume();
    $form = formie()->form()->fileUploadField('document')->create();
    $asset = UploadTestHelper::seedAsset('bounded-expiry.txt', 'expiry');
    $settings = Formie::$plugin->getSettings();
    $original = $settings->maxIncompleteSubmissionAge;
    $settings->maxIncompleteSubmissionAge = $retention;
    try {
        $before = time();
        Formie::$plugin->getFileUploads()->trackSubmissionAsset($asset, (int)$form->id, null, $form->getFieldByHandle('document')->uid, $form, 'document');
        $expires = (int)Formie::$plugin->getFileUploads()->getTrackedUploadByAssetId((int)$asset->id)['expiresAt'];
        expect($expires)->toBeGreaterThanOrEqual($before + 86400 * $days)
            ->toBeLessThanOrEqual(time() + 86400 * $days);
    } finally {
        $settings->maxIncompleteSubmissionAge = $original;
    }
})->with([[0, 30], [-1, 30], [365, 30], [1, 1]]);

it('applies the staging budget to client field-data uploads before saving the submission', function (): void {
    WebRequestTestHelper::withWebRequestContext(function (): void {
        UploadTestHelper::ensureUploadVolume();
        $form = formie()->form()->fileUploadField('document', ['restrictFiles' => false])->settings(['disableCaptchas' => true])->create();
        $settings = Formie::$plugin->getSettings();
        $original = $settings->maxStagedUploadBytes;
        $settings->maxStagedUploadBytes = 3;
        $submission = new \verbb\formie\elements\Submission();
        $submission->setForm($form);
        $submission->setFieldValueFromRequest('document', [['filename' => 'budget-client.txt', 'fileData' => 'data:text/plain;base64,' . base64_encode('four')]]);
        try {
            expect(fn() => runSubmissionCommand(submissionCommand(['form' => $form, 'submission' => $submission])))
                ->toThrow(BadRequestHttpException::class, 'staged upload budget');
            expect($submission->id)->toBeNull();
        } finally {
            $settings->maxStagedUploadBytes = $original;
        }
    });
});
