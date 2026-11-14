<?php

declare(strict_types=1);

use craft\elements\User;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\IntegrationsController;
use verbb\formie\Formie;
use verbb\formie\integrations\payments\Stripe;
use verbb\formie\models\Subscription;

function deleteIntegrationThroughController(int $integrationId): array
{
    return WebRequestTestHelper::withWebRequestContext(function ($request) use ($integrationId): array {
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        $request->setIsCpRequest(true);
        $request->setBodyParams(['id' => $integrationId, $request->csrfParam => $request->getCsrfToken()]);

        $controller = new IntegrationsController('integrations', Formie::$plugin);

        return $controller->runAction('delete-integration')->data;
    }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json']]);
}

it('tells the user why a payment integration with live subscriptions cannot be deleted', function (): void {
    $integration = new Stripe(['name' => 'Protected', 'handle' => 'protected' . bin2hex(random_bytes(6))]);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();

    $subscription = new Subscription(['integrationId' => $integration->id, 'reference' => 'sub_live_' . uniqid(), 'status' => 'active']);
    expect(Formie::$plugin->getSubscriptions()->saveSubscription($subscription))->toBeTrue();

    try {
        expect(deleteIntegrationThroughController($integration->id))->toBe([
            'success' => false,
            'error' => 'Cancel or complete active subscriptions before deleting this payment integration.',
        ]);

        expect(Formie::$plugin->getIntegrations()->getIntegrationById($integration->id))->not->toBeNull();
    } finally {
        Formie::$plugin->getSubscriptions()->deleteSubscription($subscription);
        Formie::$plugin->getIntegrations()->deleteIntegration($integration);
    }
});

it('keeps the generic message when the integration no longer exists', function (): void {
    expect(deleteIntegrationThroughController(999999))->toBe([
        'success' => false,
        'error' => 'Unable to delete integration.',
    ]);
});
