<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\IntegrationsController;

it('rejects integration oauth callbacks with an invalid state parameter', function (): void {
    WebRequestTestHelper::withWebRequestContext(function (): void {
        Craft::$app->getConfig()->getGeneral()->isSystemLive = true;

        $controller = new IntegrationsController('formie-integrations-oauth-security', Craft::$app);
        $response = $controller->runAction('callback', [
            'state' => 'invalid-oauth-state-' . uniqid(),
            'code' => 'unused-authorization-code',
        ]);

        expect($response->statusCode)->toBe(302);
    }, [
        'method' => 'GET',
        'queryParams' => [
            'state' => 'invalid-oauth-state-' . uniqid(),
            'code' => 'unused-authorization-code',
        ],
    ]);
})->group('security');
