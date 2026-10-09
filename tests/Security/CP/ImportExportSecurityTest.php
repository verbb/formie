<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\ImportExportController;
use verbb\formie\services\FormImportFiles;
use yii\web\BadRequestHttpException;
use yii\web\HttpException;
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

    expect(fn() => $controller->actionImportConfigure($filename))
        ->toThrow(BadRequestHttpException::class);
})->with([
    'parent traversal' => ['../.env'],
    'nested traversal' => ['formie-import-0199a81f-c823-7000-89e8-1c4a874a2fbb.json/../../.env'],
    'absolute path' => ['/tmp/formie-import-0199a81f-c823-7000-89e8-1c4a874a2fbb.json'],
    'wrong prefix' => ['craft-import-0199a81f-c823-7000-89e8-1c4a874a2fbb.json'],
    'wrong extension' => ['formie-import-0199a81f-c823-7000-89e8-1c4a874a2fbb.php'],
])->group('security');

it('reports when a form import file is no longer available', function (): void {
    $user = \craft\elements\User::find()->admin(true)->one();

    WebRequestTestHelper::withWebRequestContext(function () use ($user): void {
        Craft::$app->getRequest()->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity($user);

        $controller = new ImportExportController('formie-import-export-security', Craft::$app);

        expect(fn() => $controller->actionImportConfigure('formie-import-0199a81f-c823-7000-89e8-1c4a874a2fbb.json'))
            ->toThrow(HttpException::class, 'This form import has expired or is no longer available. Upload the JSON file again.');
    }, [
        'method' => 'GET',
        'requestUri' => '/admin/formie/settings/import-export/import-configure',
    ]);
})->group('security');

it('encodes hostile import preview strings before rendering the summary with raw', function (): void {
    $controller = new SecurityImportExportControllerProbe('formie-import-export-security', Craft::$app);
    $importFiles = new FormImportFiles(Craft::$app->getAssets()->getTempAssetUploadFs());
    $user = \craft\elements\User::find()->admin(true)->one();
    $userId = (int)$user->id;

    $payload = [
        'title' => '<img src=x onerror=alert(1)>',
        'handle' => '"><script>alert(1)</script>',
        'pages' => [],
        'notifications' => [
            ['name' => '<svg onload=alert(1)>'],
        ],
    ];

    $stream = fopen('php://temp', 'w+b');
    fwrite($stream, json_encode($payload, JSON_THROW_ON_ERROR));
    rewind($stream);
    $filename = $importFiles->store($stream, $userId);
    fclose($stream);

    try {
        WebRequestTestHelper::withWebRequestContext(function () use ($controller, $filename, $user): void {
            Craft::$app->getRequest()->setIsCpRequest(true);
            Craft::$app->getUser()->setIdentity($user);

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
        $importFiles->delete($filename, $userId);
    }
})->group('security');
