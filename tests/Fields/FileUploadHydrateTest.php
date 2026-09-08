<?php

declare(strict_types=1);

use Craft;
use Tests\Support\UploadTestHelper;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\FileUploadController;
use verbb\formie\fields\FileUpload;
use verbb\formie\Formie;
use yii\web\BadRequestHttpException;

function decodeControllerJsonPayload(mixed $data): array
{
    if (is_array($data)) {
        return $data;
    }

    if (is_string($data)) {
        $decoded = json_decode($data, true);

        return is_array($decoded) ? $decoded : [];
    }

    return [];
}

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
        $payload = decodeControllerJsonPayload($response->data);

        expect($payload['success'] ?? false)->toBeTrue()
            ->and($payload['assets'] ?? [])->toHaveCount(1)
            ->and($payload['assets'][0]['assetId'] ?? null)->toBe((int)$asset->id)
            ->and($payload['assets'][0]['filename'] ?? null)->toContain('upload-hydrate');
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
        ->fileUploadField('documents', [
            'restrictFiles' => false,
            'allowedKinds' => ['text'],
        ])
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
        $payload = decodeControllerJsonPayload($response->data);

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

it('resolves nested group file upload fields for upload manager context', function (): void {
    $volume = UploadTestHelper::ensureUploadVolume();
    $rows = [[
        'fields' => [[
            'type' => FileUpload::class,
            'handle' => 'nestedDocuments',
            'label' => 'Nested Documents',
            'restrictFiles' => false,
            'allowedKinds' => ['text'],
        ]],
    ]];

    $form = formie()
        ->form(['title' => 'Nested Group File Upload Context'])
        ->groupField('detailsGroup', ['rows' => $rows])
        ->create();

    $groupField = $form->getFieldByHandle('detailsGroup');
    $nestedField = $groupField?->getFieldByHandle('nestedDocuments');
    expect($nestedField)->not->toBeNull()
        // Top-level handle lookup cannot see Group children — Upload Manager posts valueKey.
        ->and($form->getFieldByHandle('detailsGroup.nestedDocuments'))->toBeNull();

    $asset = UploadTestHelper::seedAsset('nested-group-upload.txt', 'tracked', $volume);
    Formie::$plugin->getFileUploads()->trackSubmissionAsset($asset, (int)$form->id, null, $nestedField->uid);

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form, $asset): void {
        $request->setBodyParams([
            'handle' => (string)$form->handle,
            'fieldHandle' => 'detailsGroup.nestedDocuments',
            'assetIds' => [(int)$asset->id],
        ]);

        $controller = new FileUploadController('formie-file-upload-hydrate-nested', Craft::$app);
        $response = $controller->actionHydrate();
        $payload = decodeControllerJsonPayload($response->data);

        expect($payload['success'] ?? false)->toBeTrue()
            ->and($payload['assets'] ?? [])->toHaveCount(1)
            ->and($payload['assets'][0]['assetId'] ?? null)->toBe((int)$asset->id);
    }, [
        'method' => 'POST',
        'headers' => [
            'Accept' => 'application/json',
        ],
    ]);

    // Nested valueKey must clear the field lookup — failure after that is "no file", not "invalid field".
    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form): void {
        $request->setBodyParams([
            'handle' => (string)$form->handle,
            'fieldHandle' => 'detailsGroup.nestedDocuments',
        ]);

        $controller = new FileUploadController('formie-file-upload-upload-nested', Craft::$app);

        expect(fn() => $controller->actionUpload())
            ->toThrow(BadRequestHttpException::class, 'No file was uploaded.');
    }, [
        'method' => 'POST',
        'remoteAddr' => '198.51.100.94',
        'headers' => [
            'Accept' => 'application/json',
        ],
    ]);
});
