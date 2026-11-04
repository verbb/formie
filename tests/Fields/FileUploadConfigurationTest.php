<?php

declare(strict_types=1);

use craft\helpers\ArrayHelper;
use craft\helpers\Assets;
use Tests\Support\UploadTestHelper;
use verbb\formie\elements\Submission;
use verbb\formie\fields\FileUpload;

beforeEach(function (): void {
    $this->uploadKindCaches = [
        '_fileKinds' => Assets::getFileKinds(),
        '_allowedFileKinds' => Assets::getAllowedFileKinds(),
    ];
    $config = Craft::$app->getConfig()->getGeneral();
    $config->extraAllowedFileExtensions(['CADX', 'logx', 'svg', 'html', 'xml', 'ai', 'eps']);
    $config->extraFileKinds = ArrayHelper::merge($config->extraFileKinds, [
        'design' => ['label' => 'Design files', 'extensions' => ['cadx', 'blockedcad']],
        'text' => ['extensions' => ['logx']],
        'illustrator' => ['extensions' => ['eps']],
    ]);

    // Craft builds these once per request. Apply this test's config to the already-built caches.
    $kinds = ArrayHelper::merge($this->uploadKindCaches['_fileKinds'], $config->extraFileKinds);
    (new ReflectionProperty(Assets::class, '_fileKinds'))->setValue(null, $kinds);
    (new ReflectionProperty(Assets::class, '_allowedFileKinds'))->setValue(null, array_filter(
        $kinds,
        fn(array $kind): bool => (bool)array_intersect($kind['extensions'], $config->allowedFileExtensions),
    ));
});

afterEach(function (): void {
    foreach ($this->uploadKindCaches as $name => $value) {
        (new ReflectionProperty(Assets::class, $name))->setValue(null, $value);
    }
});

dataset('configured upload types', [
    'custom kind' => ['drawing.CADX', 'design', 'Custom design format content.'],
    'extended existing kind' => ['report.logx', 'text', "%PDF-1.4\n%%EOF\n"],
    'SVG' => ['drawing.svg', 'image', '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"></svg>'],
    'HTML' => ['document.html', 'html', '<!DOCTYPE html><title>Document</title>'],
    'XML' => ['document.xml', 'xml', '<?xml version="1.0"?><document/>'],
    'PDF-compatible AI' => ['drawing.ai', 'illustrator', "%PDF-1.4\n%%EOF\n"],
    'EPS' => ['drawing.eps', 'illustrator', "%!PS-Adobe-3.0 EPSF-3.0\n%%BoundingBox: 0 0 10 10\n%%EOF\n"],
]);

it('uses Craft configuration for temporary files and decoded uploads', function (string $filename, string $kind, string $contents, string $source): void {
    UploadTestHelper::ensureUploadVolume();
    $form = formie()->form()->fileUploadField('document', [
        'restrictFiles' => true,
        'allowedKinds' => [$kind],
    ])->create();
    $field = $form->getFieldByHandle('document');
    $submission = new Submission();
    $submission->setForm($form);
    $path = $source === 'path' ? tempnam(sys_get_temp_dir(), 'formie-upload-config-') : null;

    try {
        if ($path !== null) {
            file_put_contents($path, $contents);
        }

        // Neither the supplied MIME nor a guessed content kind defines the site's extension policy.
        $upload = ['filename' => $filename, 'mimeType' => 'application/octet-stream', 'type' => 'upload'];
        $upload[$source] = $path ?? $contents;
        $submission->setFieldValueFromRequest('document', ['mutationData' => [$upload]]);
        $submission->getFieldValue('document');
        $field->validateFileType($submission);

        expect($submission->getErrors('document'))->toBe([])
            ->and($field->getAccept())->toContain('.' . strtolower(pathinfo($filename, PATHINFO_EXTENSION)));
    } finally {
        if ($path !== null && is_file($path)) {
            unlink($path);
        }
    }
})->with('configured upload types')->with(['path', 'data']);

it('exposes configured kinds and only their globally allowed extensions', function (): void {
    $field = new FileUpload(['restrictFiles' => true, 'allowedKinds' => ['design']]);

    expect(array_column($field->getFileKindOptions(), 'value'))->toContain('design')
        ->and($field->getAllowedExtensions())->toBe(['cadx'])
        ->and($field->getAccept())->toBe('.cadx')
        ->and($field->getUploadTypeValidationErrors('drawing.blockedcad'))->not->toBeEmpty();
});

it('keeps per-field restrictions for configured file types', function (string $filename, string $kind, string $contents): void {
    $field = new FileUpload(['restrictFiles' => true, 'allowedKinds' => ['pdf']]);

    expect($field->getUploadTypeValidationErrors($filename, data: $contents))->not->toBeEmpty()
        ->and($field->getAccept())->not->toContain('.' . strtolower(pathinfo($filename, PATHINFO_EXTENSION)));
})->with('configured upload types');

it('does not let unrestricted fields bypass Craft extension policy', function (): void {
    $field = new FileUpload(['restrictFiles' => false, 'allowedKinds' => ['pdf']]);

    expect($field->getUploadTypeValidationErrors('drawing.cadx', data: 'Custom design content.'))->toBe([])
        ->and($field->getUploadTypeValidationErrors('drawing.blockedcad', mimeType: 'application/pdf'))->not->toBeEmpty()
        ->and($field->getUploadTypeValidationErrors('no-extension'))->not->toBeEmpty()
        ->and($field->getUploadTypeValidationErrors(''))->not->toBeEmpty()
        ->and($field->getAccept())->toBeNull();
});

it('persists a configured custom upload and retains its bytes after reload', function (): void {
    UploadTestHelper::ensureUploadVolume();
    $form = formie()->form()->fileUploadField('document', ['allowedKinds' => ['design']])->create();
    $contents = 'Custom design file contents.';
    $submission = formie()->submission($form)->with(['document' => [[
        'filename' => 'drawing.cadx',
        'fileData' => 'data:application/octet-stream;base64,' . base64_encode($contents),
    ]]])->save();
    $saved = Submission::find()->id($submission->id)->status(null)->isIncomplete(null)->isSpam(null)->one();
    $asset = $saved->getFieldValue('document')->one();

    expect($asset)->not->toBeNull()
        ->and($asset->getExtension())->toBe('cadx')
        ->and(file_get_contents($asset->getCopyOfFile()))->toBe($contents);
});
