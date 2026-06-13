<?php

declare(strict_types=1);

use verbb\formie\integrations\emailmarketing\Mailchimp;
use verbb\formie\services\Integrations;

it('defaults new integrations to site scope when admin changes are disabled', function (): void {
    Craft::$app->getConfig()->getGeneral()->allowAdminChanges = false;

    expect(Integrations::resolveScopeForNew(null))->toBe(Integrations::SCOPE_SITE)
        ->and(Integrations::resolveScopeForNew(Integrations::SCOPE_PROJECT))->toBe(Integrations::SCOPE_SITE);
});

it('allows project scope when admin changes are enabled', function (): void {
    Craft::$app->getConfig()->getGeneral()->allowAdminChanges = true;

    expect(Integrations::resolveScopeForNew(null))->toBe(Integrations::SCOPE_SITE)
        ->and(Integrations::resolveScopeForNew(Integrations::SCOPE_PROJECT))->toBe(Integrations::SCOPE_PROJECT);
});

it('treats site-scoped integrations as editable regardless of admin changes', function (): void {
    Craft::$app->getConfig()->getGeneral()->allowAdminChanges = false;

    $integration = new Mailchimp([
        'handle' => 'mailchimp',
        'scope' => Integrations::SCOPE_SITE,
    ]);

    expect($integration->isSiteScope())->toBeTrue()
        ->and($integration->canEdit())->toBeTrue()
        ->and($integration->canDelete())->toBeTrue()
        ->and($integration->getScopeLabel())->toBe('');
});

it('treats project-scoped integrations as read-only when admin changes are disabled', function (): void {
    Craft::$app->getConfig()->getGeneral()->allowAdminChanges = false;

    $integration = new Mailchimp([
        'handle' => 'mailchimp',
        'scope' => Integrations::SCOPE_PROJECT,
    ]);

    expect($integration->isProjectScope())->toBeTrue()
        ->and($integration->canEdit())->toBeFalse()
        ->and($integration->canDelete())->toBeFalse()
        ->and($integration->getScopeLabel())->toBe('Project');
});

it('defaults missing scope to project', function (): void {
    $integration = new Mailchimp(['handle' => 'mailchimp']);

    expect($integration->getScope())->toBe(Integrations::SCOPE_PROJECT)
        ->and($integration->isProjectScope())->toBeTrue();
});
