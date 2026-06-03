<?php

declare(strict_types=1);

use Craft;
use Tests\Support\UploadTestHelper;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\FileUploadController;
use verbb\formie\Formie;

it('hydrates tracked upload assets when upload context is provided', function (): void {
    $volume = UploadTestHelper::ensureUploadVolume();
    $form = formie()
        ->form(['title' => 'File Upload Hydrate'])
        ->fileUploadField('documents')
        ->create();
    $field = $form->getFieldByHandle('documents');
    $asset = UploadTestHelper::seedAsset('upload-hydrate.txt', 'tracked', $volume);

    Formie::$plugin->getFileUploads()->trackSubmissionAsset($asset, (int)$form->id, null, $field->uid);

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form, $field, $asset): void {
        $request->setBodyParams([
            'handle' => (string)$form->handle,
            'fieldHandle' => (string)$field->handle,
            'assetIds' => [(int)$asset->id],
        ]);

        $controller = new FileUploadController('formie-file-upload-hydrate', Craft::$app);
        $response = $controller->actionHydrate();
        $payload = json_decode($response->data, true);

        expect($payload['success'] ?? false)->toBeTrue()
            ->and($payload['assets'] ?? [])->toHaveCount(1)
            ->and($payload['assets'][0]['assetId'] ?? null)->toBe((int)$asset->id)
            ->and($payload['assets'][0]['filename'] ?? null)->toBe('upload-hydrate.txt');
    }, [
        'method' => 'POST',
        'headers' => [
            'Accept' => 'application/json',
        ],
    ]);
});

it('hydrates submission-linked assets when submission uid is provided', function (): void {
    $volume = UploadTestHelper::ensureUploadVolume();
    $form = formie()
        ->form(['title' => 'File Upload Hydrate Submission'])
        ->fileUploadField('documents')
        ->create();
    $asset = UploadTestHelper::seedAsset('upload-hydrate-submission.txt', 'tracked', $volume);
    $submission = formie()
        ->submission($form)
        ->with(['documents' => [$asset->id]])
        ->save();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form, $submission, $asset): void {
        $request->setBodyParams([
            'handle' => (string)$form->handle,
            'fieldHandle' => 'documents',
            'submissionUid' => (string)$submission->uid,
            'assetIds' => [(int)$asset->id],
        ]);

        $controller = new FileUploadController('formie-file-upload-hydrate-submission', Craft::$app);
        $response = $controller->actionHydrate();
        $payload = json_decode($response->data, true);

        expect($payload['success'] ?? false)->toBeTrue()
            ->and($payload['assets'] ?? [])->toHaveCount(1)
            ->and($payload['assets'][0]['assetId'] ?? null)->toBe((int)$asset->id);
    }, [
        'method' => 'POST',
        'headers' => [
            'Accept' => 'application/json',
        ],
    ]);
});
