<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\controllers\IntegrationsController;
use verbb\formie\elements\Form;
use verbb\formie\integrations\automations\WebRequest;
use verbb\formie\integrations\helpdesk\Freshdesk;
use verbb\formie\models\Stencil;
use verbb\formie\models\StencilData;
use verbb\formie\services\Integrations;
use verbb\formie\services\Permissions;
use verbb\formie\services\Stencils as StencilsService;

use craft\elements\User;

use yii\web\BadRequestHttpException;

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

it('requires a control panel request for integration form settings config', function (): void {
    WebRequestTestHelper::withWebRequestContext(function ($request): void {
        $request->setIsCpRequest(false);

        $controller = new IntegrationsController('formie-integrations-security', Craft::$app);

        expect(fn() => $controller->actionGetIntegrationFormSettingsConfig())
            ->toThrow(BadRequestHttpException::class, 'Request must be a control panel request');
    }, [
        'method' => 'GET',
        'headers' => ['Accept' => 'application/json'],
        'queryParams' => [
            'handle' => 'mailchimp',
            'formId' => 1,
        ],
    ]);
})->group('security');

it('ignores integration settings posted by form editors without integration permission', function (): void {
    $form = formie()
        ->form(['title' => 'Integration permission boundary'])
        ->singleLineTextField('email')
        ->create();
    $name = 'integrationBoundary' . bin2hex(random_bytes(6));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);

    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, [
        'accessCp',
        'accessPlugin-formie',
        Permissions::PERM_ACCESS_FORMS,
        Permissions::PERM_MANAGE_FORMS,
    ]))->toBeTrue();
    $stencil = new Stencil([
        'name' => 'Integration permission stencil',
        'handle' => 'integrationPermissionStencil' . bin2hex(random_bytes(5)),
        'scope' => StencilsService::SCOPE_SITE,
        'data' => new StencilData([
            'settings' => [
                'integrations' => [
                    'freshdesk' => [
                        'enabled' => true,
                        'mapToContact' => true,
                    ],
                ],
            ],
        ]),
    ]);

    expect(Formie::$plugin->getStencils()->saveStencil($stencil))->toBeTrue();

    $originalUser = Craft::$app->getUser()->getIdentity();

    try {
        WebRequestTestHelper::withWebRequestContext(function ($request) use ($form, $stencil, $user): void {
            $request->setIsCpRequest(true);
            Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());
            $request->setBodyParams([
                'id' => $form->id,
                'title' => $form->title,
                'handle' => $form->handle,
                'settings' => [
                    'integrations' => [
                        'freshdesk' => [
                            'enabled' => true,
                            'mapToContact' => true,
                        ],
                    ],
                ],
            ]);

            $populated = Formie::$plugin->getForms()->buildFormFromPost();

            expect($populated->settings->integrations)->not->toHaveKey('freshdesk');

            $request->setBodyParams([
                'title' => 'Stencil integration permission boundary',
                'handle' => 'stencilIntegrationBoundary' . bin2hex(random_bytes(5)),
                'applyStencilId' => $stencil->id,
            ]);

            $fromStencil = Formie::$plugin->getForms()->buildFormFromPost();

            expect($fromStencil->settings->integrations)->not->toHaveKey('freshdesk');
        }, ['method' => 'POST']);
    } finally {
        Craft::$app->getUser()->setIdentity($originalUser);
    }
})->group('security');

it('applies only explicitly declared form settings to an integration', function (): void {
    $service = new Integrations();
    $integration = new Freshdesk([
        'name' => 'Freshdesk',
        'handle' => 'freshdesk',
        'enabled' => true,
        'apiDomain' => 'https://example.freshdesk.com',
        'apiKey' => 'global-secret',
    ]);

    $filtered = $service->filterIntegrationFormSettings($integration, [
        'mapToContact' => true,
        'apiUrl' => 'http://169.254.169.254/latest/meta-data/',
        'apiKey' => 'form-secret',
        'contactFieldMapping' => ['email' => 'email'],
    ]);

    expect($filtered)->toBe([
        'mapToContact' => true,
        'contactFieldMapping' => ['email' => 'email'],
    ]);

    $formIntegration = $service->populateIntegrationFromFormSettings($integration, [
        'enabled' => false,
        'apiDomain' => 'http://169.254.169.254/latest/meta-data/',
        'apiKey' => 'form-secret',
        'mapToContact' => true,
        'contactFieldMapping' => ['email' => 'email'],
    ]);

    expect($formIntegration)->not->toBe($integration)
        ->and($formIntegration->apiDomain)->toBe('https://example.freshdesk.com')
        ->and($formIntegration->apiKey)->toBe('global-secret')
        ->and($formIntegration->getEnabled())->toBeTrue()
        ->and($formIntegration->mapToContact)->toBeTrue()
        ->and($formIntegration->contactFieldMapping)->toBe(['email' => 'email'])
        ->and($integration->mapToContact)->toBeFalse()
        ->and($integration->contactFieldMapping)->toBeNull();
})->group('security');

it('allows explicitly declared form-level endpoint settings', function (): void {
    $service = new Integrations();
    $integration = new WebRequest([
        'name' => 'Webhook',
        'handle' => 'webhook',
        'enabled' => true,
    ]);

    expect($service->filterIntegrationFormSettings($integration, [
        'url' => 'https://example.com/webhook',
        'method' => 'POST',
        'apiDomain' => 'https://example.com',
    ]))->toBe([
        'url' => 'https://example.com/webhook',
        'method' => 'POST',
    ]);
})->group('security');

it('discards global integration settings when a form is persisted', function (): void {
    $originalIntegrations = Formie::$plugin->getIntegrations();

    try {
        $fixture = new Freshdesk([
            'name' => 'Freshdesk',
            'handle' => 'freshdesk',
            'enabled' => true,
            'apiDomain' => 'https://example.freshdesk.com',
            'apiKey' => 'global-secret',
        ]);

        $registry = new class extends Integrations {
            public Freshdesk $fixture;

            public function getAllIntegrations(): array
            {
                return [$this->fixture];
            }

            public function getAllCaptchas(): array
            {
                return [];
            }

            public function getIntegrationByHandle(string $handle): ?\verbb\formie\base\IntegrationInterface
            {
                return $handle === $this->fixture->handle ? $this->fixture : null;
            }
        };
        $registry->fixture = $fixture;
        Formie::$plugin->set('integrations', $registry);

        $form = Formie::$plugin->getFactories()
            ->form(['title' => 'Integration settings boundary'])
            ->singleLineTextField('email')
            ->integrations([
                'freshdesk' => [
                    'enabled' => true,
                    'apiDomain' => 'http://169.254.169.254/latest/meta-data/',
                    'apiKey' => 'form-secret',
                    'mapToContact' => true,
                    'contactFieldMapping' => ['email' => 'email'],
                ],
            ])
            ->create();

        $savedForm = Form::find()->id($form->id)->status(null)->one();
        $savedSettings = $savedForm?->settings->integrations['freshdesk'] ?? [];

        expect($savedSettings)->toBe([
            'enabled' => true,
            'mapToContact' => true,
            'contactFieldMapping' => ['email' => 'email'],
        ]);

        $stencilSettings = StencilData::getSerializedFormSettings([
            'integrations' => [
                'freshdesk' => [
                    'enabled' => true,
                    'apiDomain' => 'http://169.254.169.254/latest/meta-data/',
                    'apiKey' => 'form-secret',
                    'mapToContact' => true,
                ],
            ],
        ]);

        expect($stencilSettings['integrations']['freshdesk'])->toBe([
            'enabled' => true,
            'mapToContact' => true,
        ]);

        $formIntegration = $registry->getAllEnabledIntegrationsForForm($savedForm)[0];

        expect($formIntegration->apiDomain)->toBe('https://example.freshdesk.com')
            ->and($formIntegration->apiKey)->toBe('global-secret')
            ->and($formIntegration->mapToContact)->toBeTrue()
            ->and($fixture->mapToContact)->toBeFalse();
    } finally {
        Formie::$plugin->set('integrations', $originalIntegrations);
    }
})->group('security');
