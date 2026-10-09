<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\ImportExportController;
use verbb\formie\Formie;
use verbb\formie\helpers\ImportExportHelper;
use verbb\formie\services\FormImportFiles;
use verbb\formie\services\Permissions;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
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

it('rejects import temp filenames outside the generated import file pattern', function(string $filename): void {
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

it('reports when a form import file is no longer available', function(): void {
    $user = \craft\elements\User::find()->admin(true)->one();

    WebRequestTestHelper::withWebRequestContext(function() use ($user): void {
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

it('encodes hostile import preview strings before rendering the summary with raw', function(): void {
    $controller = new SecurityImportExportControllerProbe('formie-import-export-security', Craft::$app);
    $importFiles = new FormImportFiles(Craft::$app->getAssets()->getTempAssetUploadFs());
    $user = \craft\elements\User::find()->admin(true)->one();
    $userId = (int)$user->id;

    $payload = [
        'title' => '<img src=x onerror=alert(1)> [unsafe](javascript:alert(4))',
        'handle' => '"><script>alert(1)</script>',
        'pages' => [[
            'rows' => [[
                'fields' => [[
                    'type' => '<img src=x onerror=alert(2)> [unsafe](javascript:alert(2)) ![image](data:text/html,unsafe)',
                    'label' => 'Unsafe type',
                    'handle' => 'unsafeType',
                ]],
            ]],
        ]],
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
        WebRequestTestHelper::withWebRequestContext(function() use ($controller, $filename, $user): void {
            Craft::$app->getRequest()->setIsCpRequest(true);
            Craft::$app->getUser()->setIdentity($user);

            $controller->actionImportConfigure($filename);

            $summary = (string)$controller->capturedSummary;

            expect($summary)
                ->toContain('&lt;img src=x onerror=alert(1)&gt;')
                ->toContain('&lt;img src=x onerror=alert(2)&gt;')
                ->toContain('&lt;svg onload=alert(1)&gt;')
                ->and($summary)->not->toContain('<img src=x onerror=alert(1)>')
                ->and($summary)->not->toContain('<img src=x onerror=alert(2)>')
                ->and($summary)->not->toContain('<svg onload=alert(1)>')
                ->and($summary)->not->toContain('href="javascript:')
                ->and($summary)->not->toContain('src="data:text')
                ->and($summary)->not->toContain('<a ')
                ->and($summary)->not->toContain('<img ');
        }, [
            'method' => 'GET',
            'requestUri' => '/admin/formie/settings/import-export/import-configure',
        ]);
    } finally {
        $importFiles->delete($filename, $userId);
    }
})->group('security');

it('requires a visible form for the import completed page', function(): void {
    $controller = new ImportExportController('formie-import-export-security', Craft::$app);

    WebRequestTestHelper::withWebRequestContext(function() use ($controller): void {
        Craft::$app->getRequest()->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(null);
        $form = formie()->form(['title' => 'Restricted completed import'])->create();

        expect(fn() => $controller->actionImportCompleted((int)$form->id))
            ->toThrow(ForbiddenHttpException::class)
            ->and(fn() => $controller->actionImportCompleted(PHP_INT_MAX))
            ->toThrow(NotFoundHttpException::class);
    }, [
        'method' => 'GET',
        'requestUri' => '/admin/formie/settings/import-export/import-completed/1',
    ]);
})->group('security');

it('encodes form titles in the import completed message', function(): void {
    $html = WebRequestTestHelper::withWebRequestContext(function($request): string {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(\craft\elements\User::find()->admin(true)->one());
        $form = new \verbb\formie\elements\Form([
            'id' => 123,
            'title' => '<img src=x onerror=alert(3)>',
        ]);
        $view = Craft::$app->getView();
        $view->setTemplateMode(\craft\web\View::TEMPLATE_MODE_CP);
        $view->registerTwigExtension(new \verbb\base\web\twig\Extension());

        return $view->renderTemplate('formie/settings/import-export/import-completed', compact('form'));
    }, [
        'method' => 'GET',
        'requestUri' => '/admin/formie/settings/import-export/import-completed/123',
    ]);

    expect($html)
        ->toContain('&lt;img src=x onerror=alert(3)&gt;')
        ->and($html)->not->toContain('<img src=x onerror=alert(3)>');
})->group('security');

it('requires manage access before an import can update an existing form', function(): void {
    $target = formie()->form(['title' => 'Restricted Import Target'])->create();
    $payload = ImportExportHelper::generateFormExport($target);
    $payload['title'] = 'Forged Import Title';

    $username = 'formImportAcl' . bin2hex(random_bytes(6));
    $user = new \craft\elements\User(['username' => $username, 'email' => $username . '@example.test']);

    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();

    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());

    $permissions = [
        'accessCp',
        'accessPlugin-formie',
        Permissions::PERM_IMPORT_FORMS,
        Formie::$plugin->getPermissions()->settingsPagePermissionKey('import-export'),
    ];

    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, $permissions))->toBeTrue();

    $importFiles = new FormImportFiles(Craft::$app->getAssets()->getTempAssetUploadFs());
    $stream = fopen('php://temp', 'w+b');
    fwrite($stream, json_encode($payload, JSON_THROW_ON_ERROR));
    rewind($stream);
    $filename = $importFiles->store($stream, (int)$user->id);
    fclose($stream);

    try {
        WebRequestTestHelper::withWebRequestContext(function($request) use ($filename, $target, $user): void {
            $request->setIsCpRequest(true);
            $identity = \craft\elements\User::find()->id($user->id)->status(null)->one();
            Craft::$app->getUser()->setIdentity($identity);
            $request->setBodyParams([
                $request->csrfParam => $request->getCsrfToken(),
                'filename' => $filename,
                'formAction' => 'update',
            ]);

            expect(Formie::$plugin->getPermissions()->canManageForm($identity, $target))->toBeFalse()
                ->and(fn() => (new ImportExportController('formie-import-export-security', Formie::$plugin))->actionImportComplete())
                ->toThrow(ForbiddenHttpException::class, 'User is not permitted to update this form');
        }, [
            'method' => 'POST',
            'requestUri' => '/admin/formie/settings/import-export/import-complete',
            'headers' => ['Accept' => 'application/json'],
        ]);
    } finally {
        $importFiles->delete($filename, (int)$user->id);
    }

    expect(Formie::$plugin->getForms()->getFormById((int)$target->id)?->title)->toBe('Restricted Import Target');
})->group('security');
