<?php

declare(strict_types=1);

use verbb\formie\integrations\crm\SugarCrm;

it('uses SugarCRM built-in password grant credentials without a placeholder secret', function (): void {
    $integration = new SugarCrm([
        'name' => 'SugarCRM',
        'handle' => 'sugarCrmBuiltIn',
        'clientSecret' => 'sugar',
    ]);

    expect($integration->clientId)->toBe('sugar')
        ->and($integration->clientSecret)->toBeNull()
        ->and($integration->getClientSecret())->toBe('')
        ->and($integration->getGrant())->toBe('password');
});

it('preserves custom SugarCRM OAuth client credentials', function (): void {
    $integration = new SugarCrm([
        'name' => 'SugarCRM',
        'handle' => 'sugarCrmCustom',
        'clientId' => 'custom-client',
        'clientSecret' => 'custom-secret',
    ]);

    expect($integration->clientId)->toBe('custom-client')
        ->and($integration->clientSecret)->toBe('custom-secret')
        ->and($integration->getClientSecret())->toBe('custom-secret');
});

it('does not require a secret for SugarCRM built-in OAuth clients', function (): void {
    $integration = new SugarCrm([
        'name' => 'SugarCRM',
        'handle' => 'sugarCrmValidation' . uniqid(),
        'username' => 'person@example.test',
        'password' => 'password',
        'apiDomain' => 'https://example.sugarcrm.com',
    ]);
    $integration->setEnabled(true);

    expect($integration->validate())->toBeTrue()
        ->and($integration->getErrors('clientSecret'))->toBe([]);
});
