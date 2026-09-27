<?php

declare(strict_types=1);

use GraphQL\Error\UserError;
use verbb\formie\gql\types\input\FileUploadInputType;

it('rejects malformed graphql file data without reusing another file payload', function (string $payload, bool $afterValidFile): void {
    $values = $afterValidFile ? [['fileData' => 'data:text/plain;base64,' . base64_encode('First file'), 'filename' => 'first.txt']] : [];
    $values[] = ['fileData' => $payload, 'filename' => 'invalid.txt'];

    expect(fn() => FileUploadInputType::normalizeValue($values))->toThrow(UserError::class, 'Invalid file data provided');
})->with(['not-a-data-url', 'data:text/plain;base64,SGVsbG8=%%%'])->with([false, true]);

it('preserves distinct decoded bytes including a zero byte string in graphql uploads', function (): void {
    $values = FileUploadInputType::normalizeValue([
        ['fileData' => 'data:text/plain;base64,' . base64_encode('First file'), 'filename' => 'first.txt'],
        ['fileData' => 'data:text/plain;base64,' . base64_encode('0'), 'filename' => 'zero.txt'],
    ]);

    expect($values['mutationData'][0]['data'])->toBe('First file')
        ->and($values['mutationData'][1]['data'])->toBe('0');
});

it('bounds encoded and decoded upload bytes before accepting base64 data', function (int $size, bool $accepted): void {
    $config = Craft::$app->getConfig()->getGeneral();
    $original = $config->maxUploadFileSize;
    $config->maxUploadFileSize = 5;
    try {
        $decode = fn() => FileUploadInputType::normalizeValue([
            ['fileData' => 'data:text/plain;base64,' . base64_encode(str_repeat('x', $size)), 'filename' => 'bounded.txt'],
        ]);
        if ($accepted) {
            expect(strlen($decode()['mutationData'][0]['data']))->toBe($size);
        } else {
            expect($decode)->toThrow(UserError::class, 'Uploaded file exceeds the maximum allowed size.');
        }
    } finally {
        $config->maxUploadFileSize = $original;
    }
})->with([[4, true], [5, true], [6, false], [7, false], [300, false]]);
