<?php

declare(strict_types=1);

use Tests\Support\UploadTestHelper;
use verbb\formie\elements\Submission;
use verbb\formie\fields\FileUpload;

it('normalizes id-map payloads into fixed-order asset queries', function (): void {
    $field = new FileUpload([
        'handle' => 'attachments',
    ]);

    $normalized = $field->normalizeValue([
        ['id' => '7'],
        ['id' => ''],
        ['id' => '12'],
    ], null);

    expect($normalized->id)->toBe(['7', '12'])
        ->and($normalized->fixedOrder)->toBeTrue()
        ->and($field->serializeValue($normalized, null))->toBe([]);
});

it('ignores mutationData for id normalization while keeping posted ids', function (): void {
    $form = formie()
        ->form(['title' => 'File Upload Mutation Data Contract'])
        ->fileUploadField('attachments', ['restrictFiles' => false])
        ->create();

    $field = $form->getFieldByHandle('attachments');
    $submission = new Submission();
    $submission->setForm($form);

    $normalized = $field->normalizeValue([
        'mutationData' => [[
            'filename' => 'sample.txt',
            'data' => 'abc',
        ]],
        0 => '9',
        1 => '',
    ], $submission);

    expect($normalized->id)->toBe(['9'])
        ->and($normalized->fixedOrder)->toBeTrue();
});

it('keeps unresolved file-upload projections deterministic across wrappers', function (): void {
    $form = formie()
        ->form(['title' => 'File Upload Projection Contract'])
        ->fileUploadField('attachments', ['restrictFiles' => false])
        ->create();

    $submission = formie()
        ->submission($form)
        ->with([
            'attachments' => [
                ['id' => '7'],
                ['id' => '12'],
            ],
        ])
        ->allowValidationFailure()
        ->save();

    expect($submission->getFieldValueAsString('attachments'))->toBe('')
        ->and($submission->getFieldValueAsArray('attachments'))->toBe([])
        ->and($submission->getFieldValueForExport('attachments'))->toBe('')
        ->and((string)$submission->getFieldValueForSummary('attachments'))->toBe('')
        ->and($submission->serializeFieldValues())->toBe([]);
});

it('resolves seeded assets for file-upload projection wrappers', function (): void {
    $volume = UploadTestHelper::ensureUploadVolume();
    $asset = UploadTestHelper::seedAsset('seed-upload-contract.txt', 'file upload contract', $volume);

    $field = new FileUpload(['handle' => 'attachments']);
    $value = $field->normalizeValue([$asset->id], null);
    $json = $field->getValueAsArray($value, null);

    expect($field->serializeValue($value, null))->toBe([$asset->id])
        ->and($field->getValueAsString($value, null))->toContain('seed-upload-contract')
        ->and($field->getValueForExport($value, null))->toContain('seed-upload-contract')
        ->and((string)$field->getValueForSummary($value, null))->toContain('seed-upload-contract')
        ->and($json)->toBeArray()
        ->and($json)->not->toBeEmpty()
        ->and($json[0])->toBeArray()
        ->and($json[0])->toHaveKey('url')
        ->and($json[0])->toHaveKey('title')
        ->and((string)$json[0]['url'])->toBe((string)$asset->getUrl())
        ->and((string)$json[0]['title'])->toBe((string)$asset->title);
});
