<?php

declare(strict_types=1);

use craft\elements\User;
use craft\errors\InvalidSubpathException;
use Tests\Support\UploadTestHelper;
use verbb\formie\elements\Submission;
use verbb\formie\fields\FileUpload;
use verbb\formie\Formie;

it('requires upload location in the form builder schema', function (): void {
    $field = new FileUpload();
    $schema = $field->defineFormBuilderGeneralSchema();

    $uploadLocationWrap = collect($schema)
        ->first(fn(array $item) => ($item['$cmp'] ?? null) === 'FieldWrap' && ($item['label'] ?? null) === Craft::t('formie', 'Upload Location'));

    $uploadLocationField = collect($schema)
        ->flatMap(fn(array $item) => $item['children'] ?? [$item])
        ->flatMap(fn(array $item) => $item['children'] ?? [$item])
        ->first(fn(array $item) => ($item['name'] ?? null) === 'uploadLocationSource');

    expect($uploadLocationWrap)->not->toBeNull()
        ->and($uploadLocationWrap['required'] ?? false)->toBeTrue()
        ->and($uploadLocationField)->not->toBeNull()
        ->and($uploadLocationField['required'] ?? false)->toBeTrue()
        ->and($uploadLocationField['validation'] ?? null)->toBe('required');
});

it('falls back to the plugin default upload volume when the field source is empty', function (): void {
    $volume = UploadTestHelper::ensureUploadVolume();
    Formie::$plugin->getSettings()->fieldDefaults = [
        FileUpload::class => [
            'uploadLocationSource' => 'folder:' . $volume->uid,
        ],
    ];

    $field = new FileUpload(['uploadLocationSource' => '']);

    $method = new ReflectionMethod(FileUpload::class, '_getVolume');
    $method->setAccessible(true);

    expect($method->invoke($field)?->uid)->toBe($volume->uid);
});

it('requires upload location when no plugin default is configured', function (): void {
    Formie::$plugin->getSettings()->fieldDefaults = [];

    $field = new FileUpload([
        'label' => 'Resume',
        'handle' => 'resume',
        'uploadLocationSource' => '',
    ]);

    expect($field->validate(['uploadLocationSource']))->toBeFalse()
        ->and($field->getErrors('uploadLocationSource'))->not->toBeEmpty();
});

it('excludes temporary uploads from upload location options', function (): void {
    $field = new FileUpload();
    $values = array_column($field->getSourceOptions(), 'value');

    expect($values)->not->toContain('temp');
});

it('rejects temporary uploads as an upload location', function (): void {
    $field = new FileUpload([
        'label' => 'Resume',
        'handle' => 'resume',
        'uploadLocationSource' => 'temp',
    ]);

    expect($field->validate(['uploadLocationSource']))->toBeFalse()
        ->and($field->getErrors('uploadLocationSource'))->not->toBeEmpty();
});

it('returns a submission validation message for invalid upload locations', function (): void {
    $field = new FileUpload([
        'label' => 'Resume',
        'handle' => 'resume',
        'uploadLocationSource' => 'temp',
    ]);

    $method = new ReflectionMethod(FileUpload::class, '_getUploadLocationError');
    $method->setAccessible(true);

    expect($method->invoke($field, null))->toBe(Craft::t('app', 'The {field} field is set to an invalid volume.', [
        'field' => 'Resume',
    ]));
});

it('blocks Craft environment access in upload location subpath Twig', function (): void {
    $volume = UploadTestHelper::ensureUploadVolume();
    $field = new FileUpload([
        'uploadLocationSource' => 'folder:' . $volume->uid,
        'uploadLocationSubpath' => '{{ craft.app.cache.cachePath }}',
    ]);

    $method = new ReflectionMethod(FileUpload::class, '_findFolder');
    $method->setAccessible(true);

    expect(fn() => $method->invoke($field, new Submission()))
        ->toThrow(InvalidSubpathException::class);
})->group('security');

it('blocks callable gadgets in upload location subpath Twig', function (): void {
    $volume = UploadTestHelper::ensureUploadVolume();
    $field = new FileUpload([
        'uploadLocationSource' => 'folder:' . $volume->uid,
        'uploadLocationSubpath' => '{{ collect(["safe"]).map("strlen").first() }}',
    ]);

    $method = new ReflectionMethod(FileUpload::class, '_findFolder');
    $method->setAccessible(true);

    expect(fn() => $method->invoke($field, new Submission()))
        ->toThrow(InvalidSubpathException::class);
})->group('security');

it('resolves references in upload location subpaths', function (): void {
    $volume = UploadTestHelper::ensureUploadVolume();
    $field = new FileUpload([
        'uploadLocationSource' => 'folder:' . $volume->uid,
        'uploadLocationSubpath' => 'references/{form:handle;transform=upper}',
    ]);

    $method = new ReflectionMethod(FileUpload::class, '_findFolder');
    $method->setAccessible(true);
    $form = formie()->form(['handle' => 'folder'])->create();
    $submission = new Submission();
    $submission->setForm($form);
    $folder = $method->invoke($field, $submission);

    expect($folder->path)->toBe('references/FOLDER/');
})->group('security');

it('resolves submission user references in upload location subpaths', function (): void {
    $volume = UploadTestHelper::ensureUploadVolume();
    $user = User::find()->status(null)->username('formie-seed-user')->one();
    $field = new FileUpload([
        'uploadLocationSource' => 'folder:' . $volume->uid,
        'uploadLocationSubpath' => 'user-{user:id}',
    ]);
    $form = formie()->form()->create();
    $submission = new Submission(['userId' => $user->id]);
    $submission->setForm($form);

    $method = new ReflectionMethod(FileUpload::class, '_findFolder');
    $method->setAccessible(true);
    $folder = $method->invoke($field, $submission);

    expect($folder->path)->toBe('user-' . $user->id . '/');
})->group('security');

it('rejects Twig and invalid upload paths without creating folders', function (string $subpath): void {
    $volume = UploadTestHelper::ensureUploadVolume();
    $form = formie()->form()->create();
    $submission = new Submission();
    $submission->setForm($form);
    $field = new FileUpload([
        'uploadLocationSource' => 'folder:' . $volume->uid,
        'uploadLocationSubpath' => $subpath,
    ]);
    $before = (new \craft\db\Query())->from('{{%volumefolders}}')->where(['volumeId' => $volume->id])->count();
    $method = new ReflectionMethod(FileUpload::class, '_findFolder');

    expect(fn() => $method->invoke($field, $submission))->toThrow(InvalidSubpathException::class);
    expect((new \craft\db\Query())->from('{{%volumefolders}}')->where(['volumeId' => $volume->id])->count())->toBe($before);
})->with([
    'Twig output' => '{{ "folder"|upper }}',
    'Twig statement' => '{% set folder = "value" %}uploads',
    'object shorthand' => '{user.fullName|kebab}',
    'missing reference' => 'uploads/{custom:missing/value}',
    'traversal' => '../escape',
    'nested traversal' => 'uploads/../escape',
    'backslash traversal' => 'uploads/..\\escape',
    'empty segment' => 'uploads//escape',
])->group('security');

it('rejects traversal and Twig supplied through upload references', function (string $value): void {
    $volume = UploadTestHelper::ensureUploadVolume();
    $form = formie()->form()->create();
    $submission = new Submission(['title' => $value]);
    $submission->setForm($form);
    $field = new FileUpload([
        'uploadLocationSource' => 'folder:' . $volume->uid,
        'uploadLocationSubpath' => 'uploads/{submission:title}',
    ]);

    expect(fn() => (new ReflectionMethod(FileUpload::class, '_findFolder'))->invoke($field, $submission))
        ->toThrow(InvalidSubpathException::class);
})->with(['../escape', '{{ 7 * 7 }}', ''])->group('security');
