<?php

declare(strict_types=1);

use craft\helpers\Assets;
use Tests\Support\UploadTestHelper;
use verbb\formie\elements\Submission;
use verbb\formie\fields\FileUpload;

beforeEach(function (): void {
    $this->illustratorKindCaches = [
        '_fileKinds' => Assets::getFileKinds(),
        '_allowedFileKinds' => Assets::getAllowedFileKinds(),
    ];
    $config = Craft::$app->getConfig()->getGeneral();
    $config->allowedFileExtensions = array_values(array_unique([...$config->allowedFileExtensions, 'ai', 'eps']));
    $config->extraFileKinds['illustrator']['extensions'] = ['eps'];

    // Mirror the site's extraFileKinds setting in Craft's already-built request caches.
    $illustrator = $this->illustratorKindCaches['_fileKinds']['illustrator'];
    $illustrator['extensions'][] = 'eps';
    foreach ($this->illustratorKindCaches as $name => $kinds) {
        $kinds['illustrator'] = $illustrator;
        (new ReflectionProperty(Assets::class, $name))->setValue(null, $kinds);
    }
});

afterEach(function (): void {
    foreach ($this->illustratorKindCaches as $name => $value) {
        (new ReflectionProperty(Assets::class, $name))->setValue(null, $value);
    }
});

dataset('illustrator file contents', [
    'PDF-compatible AI' => ['artwork.ai', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n", true],
    'PostScript AI' => ['artwork.ai', "%!PS-Adobe-3.0\n%%Creator: Adobe Illustrator\n%%BoundingBox: 0 0 10 10\nshowpage\n%%EOF\n", true],
    'EPS' => ['artwork.eps', "%!PS-Adobe-3.0 EPSF-3.0\n%%BoundingBox: 0 0 10 10\nnewpath 0 0 moveto 10 10 lineto stroke\n%%EOF\n", true],
    'text renamed AI' => ['artwork.ai', 'This is plain text, not Illustrator artwork.', false],
    'text renamed EPS' => ['artwork.eps', 'This is plain text, not EPS artwork.', false],
    'PDF renamed EPS' => ['artwork.eps', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n", false],
    'image renamed AI' => ['artwork.ai', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='), false],
]);

it('validates Illustrator content from temporary files and decoded uploads', function (string $filename, string $contents, bool $valid, string $source): void {
    UploadTestHelper::ensureUploadVolume();
    $form = formie()->form()->fileUploadField('artwork', [
        'restrictFiles' => true,
        'allowedKinds' => ['illustrator'],
    ])->create();
    $field = $form->getFieldByHandle('artwork');
    $submission = new Submission();
    $submission->setForm($form);
    $path = $source === 'path' ? tempnam(sys_get_temp_dir(), 'formie-illustrator-') : null;

    try {
        if ($path !== null) {
            file_put_contents($path, $contents);
        }

        // A claimed Illustrator MIME must not override a mismatch in the actual bytes.
        $upload = ['filename' => $filename, 'mimeType' => 'application/postscript', 'type' => 'upload'];
        $upload[$source] = $path ?? $contents;
        $submission->setFieldValueFromRequest('artwork', ['mutationData' => [$upload]]);
        $submission->getFieldValue('artwork');
        $field->validateFileType($submission);

        expect($submission->getErrors('artwork'))->toHaveCount($valid ? 0 : 1)
            ->and($field->getAccept())->toContain('.ai', '.eps');
    } finally {
        if ($path !== null && is_file($path)) {
            unlink($path);
        }
    }
})->with('illustrator file contents')->with(['path', 'data']);

it('recognizes the supported Illustrator MIME variants', function (string $filename, string $mimeType): void {
    $field = new FileUpload(['restrictFiles' => true, 'allowedKinds' => ['illustrator']]);

    expect($field->getUploadTypeValidationErrors($filename, mimeType: $mimeType))->toBe([]);
})->with([
    ['artwork.AI', 'application/pdf'],
    ['artwork.ai', 'application/postscript'],
    ['artwork.ai', 'image/x-eps'],
    ['artwork.eps', 'application/postscript'],
    ['artwork.EPS', 'image/x-eps'],
]);

it('still enforces Craft and field extension restrictions for Illustrator files', function (string $restriction): void {
    $field = new FileUpload(['restrictFiles' => true, 'allowedKinds' => ['illustrator']]);

    if ($restriction === 'craft') {
        $config = Craft::$app->getConfig()->getGeneral();
        $config->allowedFileExtensions = array_values(array_diff($config->allowedFileExtensions, ['ai']));
    } else {
        $field->allowedKinds = ['pdf'];
    }

    expect($field->getUploadTypeValidationErrors('artwork.ai', mimeType: 'application/pdf'))->not->toBeEmpty();
})->with(['craft', 'field']);

it('does not accept Illustrator content for an unrelated extension', function (): void {
    $field = new FileUpload(['restrictFiles' => true, 'allowedKinds' => ['image']]);

    expect($field->getUploadTypeValidationErrors('artwork.jpg', mimeType: 'application/postscript'))->not->toBeEmpty()
        ->and($field->getUploadTypeValidationErrors('artwork.jpg', mimeType: 'application/pdf'))->not->toBeEmpty();
});
