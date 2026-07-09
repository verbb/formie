<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\ImportExportController;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class SecurityImportExportControllerProbe extends ImportExportController
{
    public ?string $capturedSummary = null;

    public function renderTemplate($template, $variables = [], $templateMode = null): Response
    {
        $this->capturedSummary = (string)($variables['summary'] ?? '');

        return new Response();
    }
}

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

it('encodes hostile import preview strings before rendering the summary with raw', function (): void {
    $controller = new SecurityImportExportControllerProbe('formie-import-export-security', Craft::$app);

    $filename = 'formie-import-260709_104500.json';
    $fileLocationMethod = new ReflectionMethod(ImportExportController::class, '_resolveImportFileLocation');
    $fileLocationMethod->setAccessible(true);
    $fileLocation = $fileLocationMethod->invoke($controller, $filename);

    $payload = [
        'title' => '<img src=x onerror=alert(1)>',
        'handle' => '"><script>alert(1)</script>',
        'pages' => [],
        'notifications' => [
            ['name' => '<svg onload=alert(1)>'],
        ],
    ];

    file_put_contents($fileLocation, json_encode($payload, JSON_THROW_ON_ERROR));

    try {
        WebRequestTestHelper::withWebRequestContext(function () use ($controller, $filename): void {
            Craft::$app->getRequest()->setIsCpRequest(true);

            $controller->actionImportConfigure($filename);

            $summary = (string)$controller->capturedSummary;

            expect($summary)
                ->toContain('&lt;img src=x onerror=alert(1)&gt;')
                ->toContain('&lt;svg onload=alert(1)&gt;')
                ->and($summary)->not->toContain('<img src=x onerror=alert(1)>')
                ->and($summary)->not->toContain('<svg onload=alert(1)>');
        }, [
            'method' => 'GET',
            'requestUri' => '/admin/formie/settings/import-export/import-configure',
        ]);
    } finally {
        if (is_file($fileLocation)) {
            unlink($fileLocation);
        }
    }
})->group('security');
