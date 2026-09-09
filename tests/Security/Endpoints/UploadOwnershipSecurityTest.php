<?php

declare(strict_types=1);

use Craft;
use Tests\Support\UploadTestHelper;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\FileUploadController;
use verbb\formie\Formie;
use verbb\formie\helpers\UploadAccess;
use yii\web\BadRequestHttpException;

function decodeUploadOwnershipPayload(mixed $data): array
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

it('rejects anonymous hydrate of another visitor tracked upload without a capability token', function (): void {
    $volume = UploadTestHelper::ensureUploadVolume();
    $form = formie()
        ->form(['title' => 'Upload Ownership Hydrate'])
        ->fileUploadField('documents')
        ->create();
    $field = $form->getFieldByHandle('documents');
    $asset = UploadTestHelper::seedAsset('upload-ownership-hydrate.txt', 'tracked', $volume);

    Formie::$plugin->getFileUploads()->trackSubmissionAsset($asset, (int)$form->id, null, $field->uid);

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form, $field, $asset): void {
        $request->setBodyParams([
            'handle' => (string)$form->handle,
            'fieldHandle' => (string)$field->handle,
            'assetIds' => [(int)$asset->id],
        ]);

        $controller = new FileUploadController('formie-file-upload-ownership-hydrate', Craft::$app);
        $payload = decodeUploadOwnershipPayload($controller->actionHydrate()->data);

        expect($payload['success'] ?? false)->toBeTrue()
            ->and($payload['assets'] ?? [])->toBe([]);
    }, [
        'method' => 'POST',
        'headers' => [
            'Accept' => 'application/json',
        ],
    ]);
})->group('security');

it('rejects anonymous delete of another visitor tracked upload without a capability token', function (): void {
    $volume = UploadTestHelper::ensureUploadVolume();
    $form = formie()
        ->form(['title' => 'Upload Ownership Delete'])
        ->fileUploadField('documents')
        ->create();
    $field = $form->getFieldByHandle('documents');
    $asset = UploadTestHelper::seedAsset('upload-ownership-delete.txt', 'tracked', $volume);

    Formie::$plugin->getFileUploads()->trackSubmissionAsset($asset, (int)$form->id, null, $field->uid);

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form, $field, $asset): void {
        $request->setBodyParams([
            'handle' => (string)$form->handle,
            'fieldHandle' => (string)$field->handle,
            'assetId' => (int)$asset->id,
        ]);

        $controller = new FileUploadController('formie-file-upload-ownership-delete', Craft::$app);

        expect(fn() => $controller->actionDelete())
            ->toThrow(BadRequestHttpException::class, 'Invalid upload capability.');
    }, [
        'method' => 'POST',
        'headers' => [
            'Accept' => 'application/json',
        ],
    ]);
})->group('security');

it('allows hydrate and delete when a matching upload capability token is supplied', function (): void {
    $volume = UploadTestHelper::ensureUploadVolume();
    $form = formie()
        ->form(['title' => 'Upload Ownership Token'])
        ->fileUploadField('documents')
        ->create();
    $field = $form->getFieldByHandle('documents');
    $asset = UploadTestHelper::seedAsset('upload-ownership-token.txt', 'tracked', $volume);

    Formie::$plugin->getFileUploads()->trackSubmissionAsset($asset, (int)$form->id, null, $field->uid);
    $token = UploadAccess::issueToken((int)$asset->id, (int)$form->id, (string)$field->uid);

    expect($token)->not->toBeNull();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form, $field, $asset, $token): void {
        $request->setBodyParams([
            'handle' => (string)$form->handle,
            'fieldHandle' => (string)$field->handle,
            'assetIds' => [(int)$asset->id],
            'uploadTokens' => [
                (int)$asset->id => $token,
            ],
        ]);

        $controller = new FileUploadController('formie-file-upload-ownership-token', Craft::$app);
        $payload = decodeUploadOwnershipPayload($controller->actionHydrate()->data);

        expect($payload['success'] ?? false)->toBeTrue()
            ->and($payload['assets'] ?? [])->toHaveCount(1)
            ->and($payload['assets'][0]['assetId'] ?? null)->toBe((int)$asset->id)
            ->and($payload['assets'][0]['uploadToken'] ?? null)->not->toBeEmpty();

        $request->setBodyParams([
            'handle' => (string)$form->handle,
            'fieldHandle' => (string)$field->handle,
            'assetId' => (int)$asset->id,
            'uploadToken' => $token,
        ]);

        $deletePayload = decodeUploadOwnershipPayload($controller->actionDelete()->data);

        expect($deletePayload['success'] ?? false)->toBeTrue();
    }, [
        'method' => 'POST',
        'headers' => [
            'Accept' => 'application/json',
        ],
    ]);
})->group('security');

it('rejects submission-uid hydrate without continuation or CP access', function (): void {
    $volume = UploadTestHelper::ensureUploadVolume();
    $form = formie()
        ->form(['title' => 'Upload Ownership Submission Uid'])
        ->fileUploadField('documents', [
            'restrictFiles' => false,
            'allowedKinds' => ['text'],
        ])
        ->create();
    $asset = UploadTestHelper::seedAsset('upload-ownership-submission.txt', 'tracked', $volume);
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

        $controller = new FileUploadController('formie-file-upload-ownership-submission', Craft::$app);
        $payload = decodeUploadOwnershipPayload($controller->actionHydrate()->data);

        expect($payload['success'] ?? false)->toBeTrue()
            ->and($payload['assets'] ?? [])->toBe([]);
    }, [
        'method' => 'POST',
        'headers' => [
            'Accept' => 'application/json',
        ],
    ]);
})->group('security');
