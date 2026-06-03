<?php

declare(strict_types=1);

use Tests\Support\UploadTestHelper;
use verbb\formie\fields\FileUpload;
use verbb\formie\Formie;

it('requires upload location in the form builder schema', function (): void {
    $field = new FileUpload();
    $schema = $field->defineFormBuilderGeneralSchema();

    $uploadLocationField = collect($schema)
        ->flatMap(fn(array $item) => $item['children'] ?? [$item])
        ->first(fn(array $item) => ($item['name'] ?? null) === 'uploadLocationSource');

    expect($uploadLocationField)->not->toBeNull()
        ->and($uploadLocationField['required'] ?? false)->toBeTrue()
        ->and($uploadLocationField['validation'] ?? null)->toBe('required');
});

it('falls back to the plugin default upload volume when the field source is empty', function (): void {
    $volume = UploadTestHelper::ensureUploadVolume();
    Formie::$plugin->getSettings()->defaultFileUploadVolume = 'folder:' . $volume->uid;

    $field = new FileUpload(['uploadLocationSource' => '']);

    $method = new ReflectionMethod(FileUpload::class, '_getVolume');
    $method->setAccessible(true);

    expect($method->invoke($field)?->uid)->toBe($volume->uid);
});

it('requires upload location when no plugin default is configured', function (): void {
    Formie::$plugin->getSettings()->defaultFileUploadVolume = '';

    $field = new FileUpload([
        'label' => 'Resume',
        'handle' => 'resume',
        'uploadLocationSource' => '',
    ]);

    expect($field->validate(['uploadLocationSource']))->toBeFalse()
        ->and($field->getErrors('uploadLocationSource'))->not->toBeEmpty();
});
