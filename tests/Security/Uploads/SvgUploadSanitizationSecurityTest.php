<?php

declare(strict_types=1);

use craft\elements\Asset;
use Tests\Support\UploadTestHelper;
use verbb\formie\elements\Submission;

const SVG_UPLOAD_PAYLOAD = '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(document.domain)"><script>alert(1)</script><circle r="5"/></svg>';

beforeEach(function (): void {
    UploadTestHelper::ensureUploadVolume();

    // Sites can turn this off for their own CP uploads, which must not expose visitor uploads.
    $config = Craft::$app->getConfig()->getGeneral();
    $this->originalSanitizeSvgUploads = $config->sanitizeSvgUploads;
    $config->sanitizeSvgUploads = false;
});

afterEach(function (): void {
    Craft::$app->getConfig()->getGeneral()->sanitizeSvgUploads = $this->originalSanitizeSvgUploads;
});

function svgUploadContents(Asset $asset): string
{
    return (string)file_get_contents($asset->getCopyOfFile());
}

it('sanitizes submitted SVG files when Craft SVG sanitizing is off', function (): void {
    $form = formie()->form(['title' => 'SVG Upload Sanitizing'])->settings(['disableCaptchas' => true])
        ->fileUploadField('attachments', ['restrictFiles' => true, 'allowedKinds' => ['image']])
        ->create();

    $submission = new Submission();
    $submission->setForm($form);
    $submission->title = 'SVG upload';
    $submission->setFieldValueFromRequest('attachments', ['mutationData' => [
        ['filename' => 'payload.svg', 'type' => 'data', 'data' => SVG_UPLOAD_PAYLOAD],
    ]]);

    expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();

    $assets = Submission::find()->id($submission->id)->status(null)->one()->getFieldValue('attachments')->all();
    $contents = svgUploadContents($assets[0]);

    expect($assets)->toHaveCount(1)
        ->and($contents)->toContain('<circle')
        ->and($contents)->not->toContain('<script')
        ->and($contents)->not->toContain('onload');
})->group('security');

it('rejects submitted SVG files that cannot be sanitized', function (): void {
    $form = formie()->form(['title' => 'Malformed SVG Upload'])->settings(['disableCaptchas' => true])
        ->fileUploadField('attachments', ['restrictFiles' => true, 'allowedKinds' => ['image']])
        ->create();

    $submission = new Submission();
    $submission->setForm($form);
    $submission->title = 'SVG upload';
    $submission->setFieldValueFromRequest('attachments', ['mutationData' => [
        ['filename' => 'broken.svg', 'type' => 'data', 'data' => '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)'],
    ]]);

    Craft::$app->getElements()->saveElement($submission);

    expect($submission->getErrors('attachments'))->not->toBeEmpty()
        ->and(Asset::find()->filename('broken*.svg')->status(null)->exists())->toBeFalse();
})->group('security');
