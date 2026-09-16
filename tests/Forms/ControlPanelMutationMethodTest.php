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
