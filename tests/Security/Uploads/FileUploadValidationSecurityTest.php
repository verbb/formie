<?php

declare(strict_types=1);

beforeEach(fn() => \Tests\Support\UploadTestHelper::ensureUploadVolume());

use verbb\formie\elements\Submission;

function seedUploadedFilesForField(object $field, Submission $submission, array $filesByParam): void
{
    $submission->setFieldValueFromRequest($field->handle, ['mutationData' => $filesByParam[$field->handle]]);
    $submission->getFieldValue($field->handle);
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

    $contents = 'not really an svg';

    seedUploadedFilesForField($field, $submission, [
        'attachments' => [[
            'filename' => 'payload.svg',
            'data' => $contents,
            'type' => 'data',
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
            'maxFileSize' => 0.001,
        ])
        ->create();

    $field = $form->getFieldByHandle('attachments');
    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValue('attachments', $field->normalizeValue([], $submission));

    $contents = str_repeat('A', 4096);

    seedUploadedFilesForField($field, $submission, [
        'attachments' => [[
            'filename' => 'payload.txt',
            'data' => $contents,
            'type' => 'data',
        ]],
    ]);

    $field->validateMaxFileSize($submission);

    expect($submission->getErrors('attachments'))->not->toBeEmpty();
})->group('security');

it('rejects disallowed extensions even when the supplied MIME is allowed', function (): void {
    $form = formie()
        ->form(['title' => 'File Upload Extension Security'])
        ->fileUploadField('attachments', [
            'restrictFiles' => true,
            'allowedKinds' => ['image'],
        ])
        ->create();

    $field = $form->getFieldByHandle('attachments');
    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValue('attachments', $field->normalizeValue([], $submission));

    $contents = 'The supplied MIME does not grant upload permission.';

    seedUploadedFilesForField($field, $submission, [
        'attachments' => [[
            'filename' => 'payload.exe',
            'data' => $contents,
            'mimeType' => 'image/jpeg',
            'type' => 'data',
        ]],
    ]);

    $field->validateFileType($submission);

    expect($submission->getErrors('attachments'))->not->toBeEmpty();
})->group('security');

it('rejects extensions disabled in Craft even when the file kind is selected', function (): void {
    $config = Craft::$app->getConfig()->getGeneral();
    $config->allowedFileExtensions = array_values(array_diff($config->allowedFileExtensions, ['svg']));

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

    $contents = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';

    seedUploadedFilesForField($field, $submission, [
        'attachments' => [[
            'filename' => 'payload.svg',
            'data' => $contents,
            'mimeType' => 'image/svg+xml',
            'type' => 'data',
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
            'maxFiles' => 1,
        ])
        ->create();

    $field = $form->getFieldByHandle('attachments');
    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValue('attachments', $field->normalizeValue([], $submission));

    $contentsOne = 'one';
    $contentsTwo = 'two';

    seedUploadedFilesForField($field, $submission, [
        'attachments' => [
            [
                'filename' => 'one.txt',
                'data' => $contentsOne,
                'type' => 'data',
            ],
            [
                'filename' => 'two.txt',
                'data' => $contentsTwo,
                'type' => 'data',
            ],
        ],
    ]);

    $field->validateFileLimit($submission);

    expect($submission->getErrors('attachments'))->not->toBeEmpty();
})->group('security');
