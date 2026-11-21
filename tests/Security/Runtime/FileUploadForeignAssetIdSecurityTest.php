<?php

declare(strict_types=1);

use Tests\Support\UploadTestHelper;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\FileUpload;
use verbb\formie\models\HtmlSubmissionRequest;
use verbb\formie\models\SubmissionExecutionResult;

// A visitor can only attach files they uploaded. Posting the ID of any other asset is refused before
// validation reads that asset, so its filename never reaches error messages or the re-rendered form.

function foreignAssetIdForm(): Form
{
    $location = 'volume:' . UploadTestHelper::ensureUploadVolume()->uid;

    return formie()->form()->settings(['disableCaptchas' => true])
        ->fileUploadField('document', ['allowedKinds' => ['image'], 'uploadLocationSource' => $location])
        ->groupField('extra', ['rows' => [['fields' => [
            ['type' => FileUpload::class, 'handle' => 'attachment', 'label' => 'Attachment', 'allowedKinds' => ['image'], 'uploadLocationSource' => $location],
        ]]]])
        ->create();
}

function foreignAssetIdSubmit(Form $form, array $fields): SubmissionExecutionResult
{
    return WebRequestTestHelper::withWebRequestContext(fn() => runHtmlSubmission(new HtmlSubmissionRequest([
        'handle' => $form->handle,
        'submitAction' => 'submit',
        'fieldParamNamespace' => 'fields',
    ])), ['method' => 'POST', 'bodyParams' => ['fields' => $fields]]);
}

function foreignAssetIdErrors(SubmissionExecutionResult $result): string
{
    return json_encode([$result->response->outcome->errors, $result->command->submission->getErrors()], JSON_UNESCAPED_UNICODE);
}

it('refuses a posted foreign asset ID without revealing its filename', function (string $kind): void {
    $form = foreignAssetIdForm();
    $filename = 'private-' . uniqid() . ($kind === 'allowed' ? '.png' : '.xlsx');
    $asset = UploadTestHelper::seedAsset($filename, 'private', UploadTestHelper::ensureUploadVolume());

    $result = foreignAssetIdSubmit($form, ['document' => [(string)$asset->id]]);

    expect($result->response->success)->toBeFalse()
        ->and(foreignAssetIdErrors($result))->not->toContain($filename)
        ->and(foreignAssetIdErrors($result))->toContain('A valid upload attachment capability is required.')
        // The rejected form is re-rendered from this value, so it must not hold the asset either.
        ->and($result->command->submission->getFieldValue('document')->ids())->toBe([])
        ->and(Submission::find()->formId($form->id)->status(null)->isIncomplete(null)->isSpam(null)->count())->toBe(0);
})->with(['allowed', 'disallowed']);

it('refuses a posted foreign asset ID in a Group field', function (): void {
    $form = foreignAssetIdForm();
    $filename = 'private-group-' . uniqid() . '.xlsx';
    $asset = UploadTestHelper::seedAsset($filename, 'private', UploadTestHelper::ensureUploadVolume());

    $result = foreignAssetIdSubmit($form, ['extra' => ['attachment' => [(string)$asset->id]]]);

    expect($result->response->success)->toBeFalse()
        ->and(foreignAssetIdErrors($result))->not->toContain($filename)
        ->and(foreignAssetIdErrors($result))->toContain('A valid upload attachment capability is required.')
        ->and($result->command->submission->getFieldValue('extra.attachment')->ids())->toBe([]);
});
