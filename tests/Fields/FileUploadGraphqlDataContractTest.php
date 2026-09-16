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
