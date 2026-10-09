<?php

declare(strict_types=1);

use verbb\formie\services\FormImportFiles;

use craft\base\LocalFsInterface;
use craft\fs\Local;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use craft\test\mockclasses\fs\MockNonLocalFs;

it('stores and reads imports through a non-local temporary filesystem', function (): void {
    $root = Craft::$app->getPath()->getTempPath() . '/formie-import-test-' . StringHelper::UUID();
    FileHelper::createDirectory($root);

    $filesystem = new MockNonLocalFs(['path' => $root]);
    $importFiles = new FormImportFiles($filesystem);
    $stream = fopen('php://temp', 'w+b');
    fwrite($stream, '{"title":"Load balanced"}');
    rewind($stream);

    try {
        $filename = $importFiles->store($stream, 42);
        $path = $importFiles->getStoragePath($filename, 42);

        expect($filesystem)->not->toBeInstanceOf(LocalFsInterface::class)
            ->and($path)->toStartWith('formie-imports/42/formie-import-')
            ->and($filesystem->fileExists($path))->toBeTrue()
            ->and($importFiles->read($filename, 42))->toBe('{"title":"Load balanced"}');

        $importFiles->delete($filename, 42);

        expect($filesystem->fileExists($path))->toBeFalse();
    } finally {
        fclose($stream);
        FileHelper::removeDirectory($root);
    }
});

it('keeps import files scoped to the user who uploaded them', function (): void {
    $root = Craft::$app->getPath()->getTempPath() . '/formie-import-test-' . StringHelper::UUID();
    FileHelper::createDirectory($root);

    $importFiles = new FormImportFiles(new MockNonLocalFs(['path' => $root]));
    $stream = fopen('php://temp', 'w+b');
    fwrite($stream, '{}');
    rewind($stream);

    try {
        $filename = $importFiles->store($stream, 42);

        expect($importFiles->read($filename, 42))->toBe('{}')
            ->and($importFiles->read($filename, 84))->toBeNull();
    } finally {
        fclose($stream);
        FileHelper::removeDirectory($root);
    }
});

it('prunes expired imports without removing current imports', function (): void {
    $root = Craft::$app->getPath()->getTempPath() . '/formie-import-test-' . StringHelper::UUID();
    FileHelper::createDirectory($root);

    $filesystem = new Local(['path' => $root]);
    $importFiles = new FormImportFiles($filesystem, 60);
    $expired = 'formie-import-0199a81f-c823-7000-89e8-1c4a874a2fbb.json';
    $current = 'formie-import-0199a81f-c823-7000-89e8-1c4a874a2fbc.json';
    $expiredPath = $importFiles->getStoragePath($expired, 42);
    $currentPath = $importFiles->getStoragePath($current, 42);

    try {
        $filesystem->createDirectory('formie-imports/42');
        $filesystem->write($expiredPath, '{}');
        $filesystem->write($currentPath, '{}');
        touch($root . '/' . $expiredPath, 100);
        touch($root . '/' . $currentPath, 190);

        $importFiles->pruneExpired(42, 200);

        expect($filesystem->fileExists($expiredPath))->toBeFalse()
            ->and($filesystem->fileExists($currentPath))->toBeTrue();
    } finally {
        FileHelper::removeDirectory($root);
    }
});
