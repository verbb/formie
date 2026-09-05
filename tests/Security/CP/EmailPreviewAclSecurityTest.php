<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\EmailController;
use verbb\formie\Formie;
use yii\base\Action;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

it('requires a control panel request for email preview', function (): void {
    WebRequestTestHelper::withWebRequestContext(function ($request): void {
        $request->setIsCpRequest(false);

        $controller = new EmailController('formie-email-security', Craft::$app);
        $controller->enableCsrfValidation = false;

        expect(fn() => $controller->beforeAction(new Action('preview', $controller)))
            ->toThrow(BadRequestHttpException::class, 'Request must be a control panel request');
    }, [
        'method' => 'POST',
        'requestUri' => '/actions/formie/email/preview',
        'bodyParams' => ['formId' => 1],
    ]);
})->group('security');

it('requires a control panel request for email test-send', function (): void {
    WebRequestTestHelper::withWebRequestContext(function ($request): void {
        $request->setIsCpRequest(false);

        $controller = new EmailController('formie-email-security', Craft::$app);
        $controller->enableCsrfValidation = false;

        expect(fn() => $controller->beforeAction(new Action('send-test-email', $controller)))
            ->toThrow(BadRequestHttpException::class, 'Request must be a control panel request');
    }, [
        'method' => 'POST',
        'requestUri' => '/actions/formie/email/send-test-email',
        'bodyParams' => ['to' => 'attacker@example.test'],
    ]);
})->group('security');

it('denies form notification access for guests via email controller ACL helper', function (): void {
    $form = formie()
        ->form(['title' => 'Email Preview ACL'])
        ->singleLineTextField('fullName')
        ->create();

    Craft::$app->getUser()->setIdentity(null);

    $controller = new EmailController('formie-email-security', Craft::$app);
    $method = new ReflectionMethod(EmailController::class, '_requireFormNotificationAccess');
    $method->setAccessible(true);

    expect(fn() => $method->invoke($controller, $form))
        ->toThrow(ForbiddenHttpException::class);

    expect(Formie::$plugin->getPermissions()->canManageForm(null, $form))->toBeFalse();
})->group('security');

it('requires access settings for stencil email preview without formId', function (): void {
    WebRequestTestHelper::withWebRequestContext(function ($request): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(null);

        $controller = new EmailController('formie-email-security', Craft::$app);
        $controller->enableCsrfValidation = false;

        expect(fn() => $controller->actionPreview())
            ->toThrow(ForbiddenHttpException::class);
    }, [
        'method' => 'POST',
        'requestUri' => '/admin/actions/formie/email/preview',
        'bodyParams' => [
            'isStencil' => '1',
            'handle' => 'missing-stencil-handle',
            'notification' => [
                'name' => 'Preview',
                'subject' => 'Hi',
                'to' => 'a@example.test',
                'from' => 'b@example.test',
                'content' => 'Body',
            ],
        ],
    ]);
})->group('security');
