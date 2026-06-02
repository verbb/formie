<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;

function seedUploadedFilesForField(object $field, array $filesByParam): void
{
    $property = new ReflectionProperty($field, '_uploadedDataFiles');
    $property->setAccessible(true);
    $property->setValue($field, $filesByParam);
}

it('rejects uploaded files whose extensions are not allowed for the field', function (): void {
    $form = formie()
        ->form(['title' => 'File Upload Type Security'])
        ->fileUploadField('attachments', [
            'restrictFiles' => true,
            'allowedKinds' => ['text'],
        ])
        ->create();

    $field = $form->getFieldByHandle('attachments');
    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValue('attachments', $field->normalizeValue([], $submission));

    $tempPath = tempnam(sys_get_temp_dir(), 'formie-upload-');
    file_put_contents($tempPath, 'not really an svg');

    seedUploadedFilesForField($field, [
        'attachments' => [[
            'filename' => 'payload.svg',
            'path' => $tempPath,
            'type' => 'upload',
        ]],
    ]);

    $field->validateFileType($submission);

    expect($submission->getErrors('attachments'))->not->toBeEmpty();
})->group('security');

it('rejects uploaded files that exceed the configured max file size', function (): void {
    $form = formie()
        ->form(['title' => 'File Upload Size Security'])
        ->fileUploadField('attachments', [
            'restrictFiles' => false,
            'sizeLimit' => 0.001,
        ])
        ->create();

    $field = $form->getFieldByHandle('attachments');
    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValue('attachments', $field->normalizeValue([], $submission));

    $tempPath = tempnam(sys_get_temp_dir(), 'formie-upload-');
    file_put_contents($tempPath, str_repeat('A', 4096));

    seedUploadedFilesForField($field, [
        'attachments' => [[
            'filename' => 'payload.txt',
            'path' => $tempPath,
            'type' => 'upload',
        ]],
    ]);

    $field->validateMaxFileSize($submission);

    expect($submission->getErrors('attachments'))->not->toBeEmpty();
})->group('security');

it('rejects uploaded files whose detected content does not match the claimed file kind', function (): void {
    $form = formie()
        ->form(['title' => 'File Upload Mime Mismatch Security'])
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
    file_put_contents($tempPath, 'definitely not a jpeg');

    seedUploadedFilesForField($field, [
        'attachments' => [[
            'filename' => 'payload.jpg',
            'path' => $tempPath,
            'mimeType' => 'image/jpeg',
            'type' => 'upload',
        ]],
    ]);

    $field->validateFileType($submission);

    expect($submission->getErrors('attachments'))->not->toBeEmpty();
})->group('security');

it('rejects active-content uploads even when the extension would otherwise be allowed', function (): void {
    $form = formie()
        ->form(['title' => 'File Upload Active Content Security'])
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
    file_put_contents($tempPath, '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

    seedUploadedFilesForField($field, [
        'attachments' => [[
            'filename' => 'payload.svg',
            'path' => $tempPath,
            'mimeType' => 'image/svg+xml',
            'type' => 'upload',
        ]],
    ]);

    $field->validateFileType($submission);

    expect($submission->getErrors('attachments'))->not->toBeEmpty();
})->group('security');

it('rejects uploaded files above the configured file-count limit', function (): void {
    $form = formie()
        ->form(['title' => 'File Upload Count Security'])
        ->fileUploadField('attachments', [
            'restrictFiles' => false,
            'limitFiles' => 1,
        ])
        ->create();

    $field = $form->getFieldByHandle('attachments');
    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValue('attachments', $field->normalizeValue([], $submission));

    $tempOne = tempnam(sys_get_temp_dir(), 'formie-upload-');
    $tempTwo = tempnam(sys_get_temp_dir(), 'formie-upload-');
    file_put_contents($tempOne, 'one');
    file_put_contents($tempTwo, 'two');

    seedUploadedFilesForField($field, [
        'attachments' => [
            [
                'filename' => 'one.txt',
                'path' => $tempOne,
                'type' => 'upload',
            ],
            [
                'filename' => 'two.txt',
                'path' => $tempTwo,
                'type' => 'upload',
            ],
        ],
    ]);

    $field->validateFileLimit($submission);

    expect($submission->getErrors('attachments'))->not->toBeEmpty();
})->group('security');
