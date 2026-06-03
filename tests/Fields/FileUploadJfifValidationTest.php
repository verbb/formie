<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;

it('rejects jfif uploads when the extension is not allowed by craft', function (): void {
    $allowedFileExtensions = Craft::$app->getConfig()->getGeneral()->allowedFileExtensions;

    if (in_array('jfif', $allowedFileExtensions, true)) {
        test()->markTestSkipped('Craft allows jfif in this environment.');
    }

    $form = formie()
        ->form(['title' => 'JFIF Upload Validation'])
        ->fileUploadField('attachments', [
            'restrictFiles' => true,
            'allowedKinds' => ['image'],
        ])
        ->create();

    $field = $form->getFieldByHandle('attachments');

    expect($field->getUploadTypeValidationErrors('photo.jfif'))
        ->not->toBeEmpty()
        ->and($field->getAccept())->not->toContain('.jfif');
})->group('security');

it('rejects jfif uploads during submission validation', function (): void {
    $allowedFileExtensions = Craft::$app->getConfig()->getGeneral()->allowedFileExtensions;

    if (in_array('jfif', $allowedFileExtensions, true)) {
        test()->markTestSkipped('Craft allows jfif in this environment.');
    }

    $form = formie()
        ->form(['title' => 'JFIF Upload Submission Validation'])
        ->fileUploadField('attachments', [
            'restrictFiles' => true,
            'allowedKinds' => ['image'],
        ])
        ->create();

    $field = $form->getFieldByHandle('attachments');
    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValue('attachments', $field->normalizeValue([], $submission));

    $tempPath = tempnam(sys_get_temp_dir(), 'formie-upload-');
    file_put_contents($tempPath, 'fake jfif content');

    $property = new ReflectionProperty($field, '_uploadedDataFiles');
    $property->setAccessible(true);
    $property->setValue($field, [
        'attachments' => [[
            'filename' => 'photo.jfif',
            'path' => $tempPath,
            'mimeType' => 'image/jpeg',
            'type' => 'upload',
        ]],
    ]);

    $field->validateFileType($submission);

    expect($submission->getErrors('attachments'))->not->toBeEmpty();
})->group('security');
