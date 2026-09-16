<?php

declare(strict_types=1);

use craft\elements\Asset;
use Tests\Support\UploadTestHelper;
use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\models\FormGroup;

it('expires regional upload assets while preserving recent uploads and submission records', function (): void {
    if (!Craft::$app->getIsMultiSite()) { $this->markTestSkipped('Multi-site contract.'); }
    $volume = UploadTestHelper::ensureUploadVolume();
    $siteId = Craft::$app->getSites()->getAllSiteIds()[1];
    $group = new FormGroup(['name' => 'Retention region', 'handle' => 'retentionRegion' . bin2hex(random_bytes(5)),
        'settings' => ['sitePolicy' => ['enabledSiteIds' => [$siteId]]]]);
    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();
    $form = formie()->form(['groupId' => $group->id, 'siteId' => $siteId, 'sourceSiteId' => $siteId])
        ->fileUploadField('documents', ['restrictFiles' => false, 'assetDataRetention' => 'days', 'assetDataRetentionValue' => '1'])->create();
    $items = [];
    foreach (['expired', 'recent'] as $age) {
        $asset = UploadTestHelper::seedAsset('regional-' . $age . '-' . bin2hex(random_bytes(4)) . '.txt', $age, $volume);
        $submission = new Submission(['siteId' => $siteId, 'title' => 'Regional ' . $age]);
        $submission->setForm($form);
        $submission->setFieldValueFromRequest('documents', [$asset->id]);
        expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();
        Formie::$plugin->getFileUploads()->trackSubmissionAsset($asset, $form->id, $submission->id, $form->getFieldByHandle('documents')->uid);
        Formie::$plugin->getFileUploads()->finalizeSubmissionUploads($submission->id);
        $items[$age] = [$asset->id, $submission->id];
    }
    \craft\helpers\Db::update('{{%elements}}', ['dateCreated' => '2000-01-01 00:00:00'], ['id' => $items['expired'][1]]);
    Formie::$plugin->getFileUploads()->pruneExpiredFieldAssets();
    expect(Asset::find()->id($items['expired'][0])->status(null)->one())->toBeNull();
    expect(Asset::find()->id($items['recent'][0])->status(null)->one())->not->toBeNull();
    expect(Submission::find()->id($items['expired'][1])->one()?->getFieldValue('documents')->ids())->toBe([]);
    expect(Submission::find()->id($items['recent'][1])->one()?->getFieldValue('documents')->ids())->toBe([$items['recent'][0]]);
});
