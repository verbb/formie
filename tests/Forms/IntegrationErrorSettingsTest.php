<?php

use craft\elements\User;
use craft\web\TemplateResponseFormatter;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\IntegrationSettingsController;
use verbb\formie\controllers\IntegrationsController;
use verbb\formie\controllers\SettingsController;
use verbb\formie\Formie;
use verbb\formie\helpers\IntegrationApiErrors;
use verbb\formie\integrations\emailmarketing\CampaignMonitor;
use verbb\formie\integrations\emailmarketing\Mailchimp;
use verbb\formie\services\Integrations;

it('renders advanced connection dropdowns collapsed except for validation errors', function(string $provider, string $mode): void {
    $settings = Formie::$plugin->getSettings();
    $previous = $settings->integrationApiErrorHandling;
    $settings->integrationApiErrorHandling = in_array($mode, ['default', 'error'], true) ? [] : [
        ['severity' => 'rate_limited', 'action' => 'logInfo'],
        ['severity' => 'rejected', 'action' => 'ignore'],
    ];
    $integration = new $provider(['name' => 'Newsletter', 'handle' => 'newsletter', 'apiErrorHandling' => $mode === 'connection' ? ['rejected' => 'failQueue'] : []]);
    if ($mode === 'error') {
        $integration->addError('apiErrorHandling', 'Invalid action.');
    }
    try {
        WebRequestTestHelper::withWebRequestContext(function($request) use ($provider, $mode, $integration): void {
            $request->setIsCpRequest(true);
            Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
            Craft::$app->getView()->registerTwigExtension(new \verbb\base\web\twig\Extension());
            $response = (new IntegrationSettingsController('integration-settings', Formie::$plugin))->actionEditEmailMarketing(null, $integration);
            (new TemplateResponseFormatter())->format($response);
            $html = $response->content;
            expect($html)->toContain('Known Subscriber Rejections', 'API Rate Limits', 'Other Errors')
                ->not->toContain('formie-api-error-policy', 'data-action-help', 'Request details remain available in Diagnostics', 'Completed jobs leave the queue');
            $document = new DOMDocument();
            @$document->loadHTML($html);
            $xpath = new DOMXPath($document);
            $prefix = 'types[' . $provider . '][apiErrorHandling]';
            $selects = $xpath->query('//select[starts-with(@name,"' . $prefix . '")]');
            expect($selects->length)->toBe(3);
            $advanced = $xpath->query('ancestor::div[contains(@id,"advanced-api-error-handling")]', $selects->item(0))->item(0);
            expect($advanced)->not->toBeNull();
            expect(str_contains($advanced->getAttribute('class'), 'hidden'))->toBe($mode !== 'error');
            $toggle = $xpath->query('//button[@data-target="' . $advanced->getAttribute('id') . '"]')->item(0);
            expect($toggle)->not->toBeNull();
            expect($toggle->getAttribute('type'))->toBe('button')
                ->and(trim($toggle->textContent))->toBe('Advanced')
                ->and(str_contains($toggle->getAttribute('class'), 'expanded'))->toBe($mode === 'error');
            $expected = match ($mode) {
                'connection' => ['failQueue', 'logInfo', 'failQueue'],
                'legacy' => ['ignore', 'logInfo', 'failQueue'],
                default => ['logWarning', 'failQueue', 'failQueue'],
            };
            foreach ($selects as $i => $select) {
                expect($select->getAttribute('name'))->toBe($prefix . '[' . ['rejected', 'rate_limited', 'failure'][$i] . ']');
                expect($xpath->query('option[@selected]', $select)->item(0)->getAttribute('value'))->toBe($expected[$i]);
                expect($xpath->query('//label[@for="' . $select->getAttribute('id') . '"]')->length)->toBe(1);
                expect($xpath->query('option', $select)->length)->toBe(4);
                expect($xpath->query('ancestor::div[contains(concat(" ",normalize-space(@class)," ")," field ")]', $select)->length)->toBeGreaterThan(0);
            }
        });
    } finally {
        $settings->integrationApiErrorHandling = $previous;
    }
})->with([Mailchimp::class, CampaignMonitor::class])->with(['default', 'legacy', 'connection', 'error']);

it('omits provider error controls from the global integrations settings page', function(): void {
    WebRequestTestHelper::withWebRequestContext(function($request): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        Craft::$app->getView()->registerTwigExtension(new \verbb\base\web\twig\Extension());
        $response = (new SettingsController('settings', Formie::$plugin))->actionIntegrationsSettings();
        (new TemplateResponseFormatter())->format($response);
        expect($response->content)->not->toContain('name="settings[integrationApiErrorHandling]', 'formie-api-error-policy', 'Integration API Error Handling');
    });
});

it('saves and reloads error choices on only the selected connection', function(string $provider, string $scope): void {
    $service = Formie::$plugin->getIntegrations();
    $projectConfig = Craft::$app->getProjectConfig();
    $other = new $provider(['name' => 'Other newsletter', 'handle' => 'otherPolicy' . bin2hex(random_bytes(4)), 'scope' => $scope, 'apiKey' => 'synthetic-key', 'apiErrorHandling' => ['rejected' => 'ignore']]);
    $handle = 'savedPolicy' . bin2hex(random_bytes(4));
    $saved = null;
    $actions = ['rejected' => 'logInfo', 'rate_limited' => 'logWarning', 'failure' => 'failQueue'];
    try {
        expect($service->saveIntegration($other))->toBeTrue();
        WebRequestTestHelper::withWebRequestContext(function($request) use ($provider, $scope, $handle, $actions, $projectConfig): void {
            Craft::$app->set('projectConfig', $projectConfig);
            $request->setIsCpRequest(true);
            Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
            $request->setBodyParams([
                $request->csrfParam => $request->getCsrfToken(),
                'name' => 'Saved newsletter', 'handle' => $handle, 'type' => $provider, 'scope' => $scope, 'enabled' => '1',
                'types' => [$provider => ['apiKey' => 'synthetic-key', 'clientId' => 'synthetic-client', 'apiErrorHandling' => $actions]],
            ]);
            expect((new IntegrationsController('integrations', Formie::$plugin))->actionSaveIntegration())->not->toBeNull();
        }, ['method' => 'POST']);
        $reloaded = new Integrations();
        $saved = $reloaded->getIntegrationByHandle($handle);
        expect($saved)->toBeInstanceOf($provider);
        expect($saved->getSettings()['apiErrorHandling'])->toBe($actions)
            ->and(IntegrationApiErrors::handlingActions($saved))->toBe($actions)
            ->and($reloaded->getIntegrationByHandle($other->handle)->apiErrorHandling)->toBe(['rejected' => 'ignore']);
        expect($saved->getFormSettingAttributes())->not->toContain('apiErrorHandling');
        if ($scope === Integrations::SCOPE_PROJECT) {
            $config = \craft\helpers\ProjectConfig::unpackAssociativeArrays(Craft::$app->getProjectConfig()->get('formie.integrations.' . $saved->uid));
            expect($config['settings']['apiErrorHandling'])->toBe($actions);
        }
    } finally {
        if ($saved) {
            $service->deleteIntegration($saved);
        }
        $service->deleteIntegration($other);
    }
})->with([Mailchimp::class, CampaignMonitor::class])->with([Integrations::SCOPE_SITE, Integrations::SCOPE_PROJECT]);

it('rejects invalid connection actions and fails closed if validation is bypassed', function(string $provider): void {
    $integration = new $provider(['name' => 'Newsletter', 'handle' => 'newsletter', 'apiKey' => 'synthetic-key', 'apiErrorHandling' => ['rejected' => 'typo']]);
    expect($integration->validate(['apiErrorHandling']))->toBeFalse()
        ->and(IntegrationApiErrors::action($integration, 'rejected'))->toBe('failQueue');
})->with([Mailchimp::class, CampaignMonitor::class]);
