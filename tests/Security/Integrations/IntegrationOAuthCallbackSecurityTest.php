<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\IntegrationsController;
use yii\web\BadRequestHttpException;

it('rejects integration oauth callbacks with an invalid state parameter', function (): void {
    $state = 'invalid-oauth-state-' . uniqid();

    expect(fn() => WebRequestTestHelper::withWebRequestContext(function (): void {
        Craft::$app->getConfig()->getGeneral()->isSystemLive = true;

        $controller = new IntegrationsController('formie-integrations-oauth-security', Craft::$app);
        $controller->runAction('callback');
    }, [
        'method' => 'GET',
        'queryParams' => [
            'state' => $state,
            'code' => 'unused-authorization-code',
        ],
    ]))->toThrow(BadRequestHttpException::class, 'invalid or has expired');
})->group('security');
