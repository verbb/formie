<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\IntegrationsController;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

it('requires a control panel request for integration form settings refresh', function (): void {
    WebRequestTestHelper::withWebRequestContext(function ($request): void {
        $request->setIsCpRequest(false);

        $controller = new IntegrationsController('formie-integrations-security', Craft::$app);

        expect(fn() => $controller->actionFormSettings())
            ->toThrow(BadRequestHttpException::class, 'Request must be a control panel request');
    }, [
        'method' => 'POST',
        'requestUri' => '/actions/formie/integrations/form-settings',
        'bodyParams' => [
            'integration' => 'mailchimp',
            'formId' => 1,
        ],
    ]);
})->group('security');

it('requires a form id for integration form settings refresh', function (): void {
    WebRequestTestHelper::withWebRequestContext(function ($request): void {
        $request->setIsCpRequest(true);

        $controller = new IntegrationsController('formie-integrations-security', Craft::$app);

        expect(fn() => $controller->actionFormSettings())
            ->toThrow(BadRequestHttpException::class, 'Missing form ID.');
    }, [
        'method' => 'POST',
        'requestUri' => '/admin/actions/formie/integrations/form-settings',
        'bodyParams' => [
            'integration' => 'mailchimp',
        ],
    ]);
})->group('security');

it('filters sensitive integration form settings before applying request attributes', function (): void {
    $controller = new IntegrationsController('formie-integrations-security', Craft::$app);
    $method = new ReflectionMethod(IntegrationsController::class, '_filterIntegrationFormSettings');
    $method->setAccessible(true);

    $filtered = $method->invoke($controller, [
        'mapToContact' => true,
        'listId' => 'abc123',
        'apiUrl' => 'http://169.254.169.254/latest/meta-data/',
        'apiKey' => 'secret-key',
        'contactFieldMapping' => ['email' => 'email'],
    ]);

    expect($filtered)->toBe([
        'mapToContact' => true,
        'listId' => 'abc123',
        'contactFieldMapping' => ['email' => 'email'],
    ]);
})->group('security');
