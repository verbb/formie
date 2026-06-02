<?php

declare(strict_types=1);

use verbb\formie\controllers\ImportExportController;
use yii\web\BadRequestHttpException;

it('rejects import temp filenames outside the generated import file pattern', function (string $filename): void {
    $controller = new ImportExportController('formie-import-export-security', Craft::$app);
    $method = new ReflectionMethod(ImportExportController::class, '_resolveImportFileLocation');
    $method->setAccessible(true);

    expect(fn() => $method->invoke($controller, $filename))
        ->toThrow(BadRequestHttpException::class);
})->with([
    'parent traversal' => ['../.env'],
    'nested traversal' => ['formie-import-260519_224500.json/../../.env'],
    'absolute path' => ['/tmp/formie-import-260519_224500.json'],
    'wrong prefix' => ['craft-import-260519_224500.json'],
    'wrong extension' => ['formie-import-260519_224500.php'],
])->group('security');

it('allows generated import temp filenames', function (): void {
    $controller = new ImportExportController('formie-import-export-security', Craft::$app);
    $method = new ReflectionMethod(ImportExportController::class, '_resolveImportFileLocation');
    $method->setAccessible(true);

    expect($method->invoke($controller, 'formie-import-260519_224500.json'))
        ->toEndWith('formie-import-260519_224500.json');
})->group('security');
