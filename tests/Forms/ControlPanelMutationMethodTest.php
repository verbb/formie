<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use yii\web\MethodNotAllowedHttpException;

it('requires POST before processing a control panel mutation', function (string $controllerName, string $action): void {
    WebRequestTestHelper::withWebRequestContext(function ($request) use ($controllerName, $action): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(\craft\elements\User::find()->admin(true)->one());
        $class = 'verbb\\formie\\controllers\\' . $controllerName . 'Controller';
        $controller = new $class('method-contract', Formie::$plugin);

        expect(fn() => $controller->runAction($action))
            ->toThrow(MethodNotAllowedHttpException::class, 'Post request required');
    }, ['method' => 'GET', 'headers' => ['Accept' => 'application/json']]);
})->with([
    ['Stencils', 'delete'],
    ['FormStatuses', 'delete'],
    ['SubmissionStatuses', 'delete'],
    ['FormTemplates', 'delete'],
    ['EmailTemplates', 'delete'],
    ['PdfTemplates', 'delete'],
    ['ScheduledReports', 'delete'],
    ['SentNotifications', 'resend'],
    ['SentNotifications', 'bulk-resend'],
    ['ImportExport', 'import'],
    ['ImportExport', 'import-complete'],
    ['Migrations', 'freeform4'],
    ['Migrations', 'freeform5'],
    ['Migrations', 'sprout-forms'],
]);

it('preserves a form status on GET and deletes it on a valid POST', function (): void {
    $status = new \verbb\formie\models\FormStatus([
        'name' => 'Temporary status',
        'handle' => 'temporary' . bin2hex(random_bytes(5)),
        'isDefault' => false,
    ]);
    expect(Formie::$plugin->getFormStatuses()->saveStatus($status))->toBeTrue();
    $stored = fn() => (new \craft\db\Query())->from(\verbb\formie\helpers\Table::FORMIE_FORM_STATUSES)->where(['id' => $status->id])->one();

    // The web helper reuses the plugin; reuse its initialized project-config listeners too.
    $projectConfig = Craft::$app->getProjectConfig();
    foreach (['GET', 'POST'] as $method) {
        WebRequestTestHelper::withWebRequestContext(function ($request) use ($status, $method, $projectConfig): void {
            Craft::$app->set('projectConfig', $projectConfig);
            $request->setIsCpRequest(true);
            Craft::$app->getUser()->setIdentity(\craft\elements\User::find()->admin(true)->one());
            if ($method === 'POST') {
                $request->setBodyParams(['id' => $status->id, $request->csrfParam => $request->getCsrfToken()]);
            }
            $controller = new \verbb\formie\controllers\FormStatusesController('form-statuses', Formie::$plugin);
            if ($method === 'GET') {
                expect(fn() => $controller->runAction('delete'))->toThrow(MethodNotAllowedHttpException::class, 'Post request required');
            } else {
                expect($controller->runAction('delete')->data)->toBe(['success' => true]);
            }
        }, ['method' => $method, 'queryParams' => ['id' => $status->id], 'headers' => ['Accept' => 'application/json']]);

        if ($method === 'GET') {
            expect($stored()['dateDeleted'])->toBeNull();
        } else {
            expect($stored()['dateDeleted'])->not->toBeNull();
        }
    }
});

it('imports a form on a valid POST', function (): void {
    $form = Formie::$plugin->getFactories()->form()->singleLineTextField('message')->create();
    $payload = \verbb\formie\helpers\ImportExportHelper::generateFormExport($form);
    $payload['handle'] = 'postedImport' . bin2hex(random_bytes(5));
    $filename = 'formie-import-' . gmdate('ymd_His') . '.json';
    $controller = new \verbb\formie\controllers\ImportExportController('import-export', Formie::$plugin);
    $location = (new ReflectionMethod($controller, '_resolveImportFileLocation'))->invoke($controller, $filename);
    file_put_contents($location, json_encode($payload, JSON_THROW_ON_ERROR));
    $projectConfig = Craft::$app->getProjectConfig();
    try {
        WebRequestTestHelper::withWebRequestContext(function ($request) use ($filename, $projectConfig): void {
            Craft::$app->set('projectConfig', $projectConfig);
            $request->setIsCpRequest(true);
            Craft::$app->getUser()->setIdentity(\craft\elements\User::find()->admin(true)->one());
            $request->setBodyParams([
                $request->csrfParam => $request->getCsrfToken(),
                'filename' => $filename,
                'formAction' => 'create',
            ]);
            // Recreate the real sites service after login so its guest permission cache is cleared.
            Craft::$app->set('sites', new \craft\services\Sites());
            (new \verbb\formie\controllers\ImportExportController('import-export', Formie::$plugin))->runAction('import-complete');
        }, ['method' => 'POST']);
        $imported = \verbb\formie\elements\Form::find()->handle($payload['handle'])->one();
        expect($imported)->not->toBeNull()
            ->and($imported?->getFieldByHandle('message'))->not->toBeNull();
    } finally {
        if (is_file($location)) { unlink($location); }
    }
});
