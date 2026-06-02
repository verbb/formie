<?php

declare(strict_types=1);

use Tests\Support\UploadTestHelper;
use Craft;
use craft\db\Query;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;

it('refuses to delete untracked upload asset ids', function (): void {
    expect(Formie::$plugin->getFileUploads()->removeUploadByAssetId(999999))->toBeFalse();
})->group('security');

it('refuses to delete finalized tracked uploads anonymously', function (): void {
    $volume = UploadTestHelper::ensureUploadVolume();
    $form = formie()
        ->form(['title' => 'Upload Authorization Finalized Security'])
        ->singleLineTextField('fullName')
        ->create();
    $submission = formie()
        ->submission($form)
        ->with(['fullName' => 'Security Tester'])
        ->save();

    $asset = UploadTestHelper::seedAsset('finalized-authorization.txt', 'finalized', $volume);
    Formie::$plugin->getFileUploads()->trackSubmissionAsset($asset, (int)$form->id, (int)$submission->id, 'field-finalized');
    Formie::$plugin->getFileUploads()->finalizeSubmissionUploads((int)$submission->id);

    $removed = Formie::$plugin->getFileUploads()->removeUploadByAssetId((int)$asset->id);
    $remaining = (new Query())
        ->from(Table::FORMIE_PENDING_UPLOADS)
        ->where(['assetId' => (int)$asset->id])
        ->exists();

    expect($removed)->toBeFalse()
        ->and($remaining)->toBeTrue();
})->group('security');

it('filters tracked upload metadata by form and field context', function (): void {
    $volume = UploadTestHelper::ensureUploadVolume();
    $formA = formie()
        ->form(['title' => 'Upload Authorization Context A'])
        ->singleLineTextField('fullName')
        ->create();
    $formB = formie()
        ->form(['title' => 'Upload Authorization Context B'])
        ->singleLineTextField('fullName')
        ->create();

    $assetA = UploadTestHelper::seedAsset('context-alpha-a.txt', 'a', $volume);
    $assetB = UploadTestHelper::seedAsset('context-beta.txt', 'b', $volume);
    $assetC = UploadTestHelper::seedAsset('context-alpha-c.txt', 'c', $volume);

    Formie::$plugin->getFileUploads()->trackSubmissionAsset($assetA, (int)$formA->id, null, 'field-alpha');
    Formie::$plugin->getFileUploads()->trackSubmissionAsset($assetB, (int)$formA->id, null, 'field-beta');
    Formie::$plugin->getFileUploads()->trackSubmissionAsset($assetC, (int)$formB->id, null, 'field-alpha');

    $metadata = Formie::$plugin->getFileUploads()->getUploadMetadata([(int)$assetA->id, (int)$assetB->id, (int)$assetC->id], (int)$formA->id, 'field-alpha');

    expect($metadata)->toHaveCount(1)
        ->and((int)$metadata[0]['assetId'])->toBe((int)$assetA->id)
        ->and((int)$metadata[0]['formId'])->toBe((int)$formA->id)
        ->and($metadata[0]['fieldUid'])->toBe('field-alpha');
})->group('security');
