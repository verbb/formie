<?php

declare(strict_types=1);

use verbb\formie\fields\Entries;
use verbb\formie\fields\FileUpload;

it('does not list existing assets in the file upload client definition', function (): void {
    $field = new FileUpload([
        'handle' => 'attachments',
        'label' => 'Attachments',
        'maxFiles' => 3,
    ]);

    $input = $field->getClientRenderedDefinition()['input'];

    expect($input)->not->toHaveKey('options')
        ->and($input)->not->toHaveKey('useSearchable')
        ->and($input)->not->toHaveKey('searchableOptionLimit')
        ->and($input)->not->toHaveKey('layout')
        ->and($input['fieldKind'])->toBe('file')
        ->and($input['multiple'])->toBeTrue()
        ->and($input['maxFiles'])->toBe(3);
});

it('keeps the option list for element fields that pick existing elements', function (): void {
    $field = new Entries([
        'handle' => 'related',
        'label' => 'Related',
    ]);

    expect($field->getClientRenderedDefinition()['input'])->toHaveKey('options');
});
