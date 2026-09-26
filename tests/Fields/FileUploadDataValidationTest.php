<?php

declare(strict_types=1);

use Tests\Support\UploadTestHelper;
use verbb\formie\elements\Submission;
use verbb\formie\gql\types\input\FileUploadInputType;

it('validates decoded data uploads against file constraints', function (array $settings, string $filename, string $contents, bool $valid): void {
    UploadTestHelper::ensureUploadVolume();
    $form = formie()->form()->fileUploadField('upload', ['restrictFiles' => false] + $settings)->create();
    $field = $form->getFieldByHandle('upload');
    $submission = new Submission();
    $submission->setForm($form);
    // The claimed MIME deliberately disagrees with the bytes in the spoofed image case.
    $submission->setFieldValueFromRequest('upload', FileUploadInputType::normalizeValue([
        ['filename' => $filename, 'fileData' => 'data:image/png;base64,' . base64_encode($contents)],
    ]));
    $submission->getFieldValue('upload');
    $field->validateFileType($submission);
    if (isset($settings['sizeLimit'])) {
        $field->validateMaxFileSize($submission);
    }
    if (isset($settings['sizeMinLimit'])) {
        $field->validateMinFileSize($submission);
    }
    expect($submission->hasErrors('upload'))->toBe(!$valid);
})->with([
    'too large' => [['sizeLimit' => '0.000005'], 'data.txt', '1234567890', false],
    'too small' => [['sizeMinLimit' => '0.000015'], 'data.txt', '1234567890', false],
    'exact limits' => [['sizeLimit' => '0.000010', 'sizeMinLimit' => '0.000010'], 'data.txt', '1234567890', true],
    'spoofed image' => [[], 'image.png', 'This is ordinary text rather than an image.', false],
]);

it('saves canonical client file data alongside retained assets', function (bool $numericId): void {
    $asset = UploadTestHelper::seedAsset('previous.txt', 'Previous content');
    $form = formie()->form()->fileUploadField('upload', ['restrictFiles' => false])->create();
    $submission = formie()->submission($form)->with(['upload' => [
        $numericId ? (int)$asset->id : ['assetId' => (int)$asset->id],
        ['filename' => 'new.txt', 'fileData' => 'data:text/plain;base64,' . base64_encode('New content')],
    ]])->save();
    $saved = Submission::find()->id($submission->id)->status(null)->isIncomplete(null)->isSpam(null)->one();
    $assets = $saved->getFieldValue('upload')->all();
    expect($assets)->toHaveCount(2)
        ->and(array_map(fn($item) => (int)$item->id, $assets))->toContain((int)$asset->id);
    $newAssets = array_values(array_filter($assets, fn($item) => $item->id != $asset->id));
    expect(file_get_contents($newAssets[0]->getCopyOfFile()))->toBe('New content');
})->with([false, true]);


it('routes client submission upload envelopes through request normalization', function (): void {
    UploadTestHelper::ensureUploadVolume();
    $form = formie()->form()->fileUploadField('upload', ['restrictFiles' => false])->settings(['disableCaptchas' => true])->create();
    \Tests\Support\WebRequestTestHelper::withWebRequestContext(function () use ($form) {
        $result = runClientSubmission(new \verbb\formie\client\models\SubmitRequest([
            'handle' => $form->handle,
            'session' => ['version' => 0, 'tokens' => ['request' => $form->getRequestToken()]],
            'values' => ['upload' => [['filename' => 'client-boundary.txt', 'fileData' => 'data:text/plain;base64,' . base64_encode('Client boundary bytes')]]],
        ]))->toArrayRecursive();
        expect($result['success'])->toBeTrue();
        $saved = Submission::find()->formId($form->id)->status(null)->isIncomplete(null)->isSpam(null)->one();
        $asset = $saved->getFieldValue('upload')->one();
        expect(file_get_contents($asset->getCopyOfFile()))->toBe('Client boundary bytes');
    }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json']]);
});
