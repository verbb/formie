<?php

declare(strict_types=1);

use verbb\formie\base\IntegrationInterface;
use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\integrations\elements\Entry as EntryIntegration;
use verbb\formie\integrations\elements\User as UserIntegration;

// The form builder must start each switch at the value a send would use when the form hasn't saved one.
function switchDefaultsIntegration(string $class): IntegrationInterface
{
    $integration = new $class(['name' => 'Switch defaults', 'handle' => 'switchDefaults' . bin2hex(random_bytes(5)), 'enabled' => true]);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration))->toBeTrue();

    return $integration;
}

function switchDefaultsBuilderValues(IntegrationInterface $integration, array $saved): array
{
    $form = new Form();
    $form->handle = 'switchDefaults' . bin2hex(random_bytes(5));
    $form->title = 'Switch defaults';
    $form->settings->integrations = [$integration->handle => ['enabled' => true] + $saved];

    $config = Formie::$plugin->getIntegrations()->getIntegrationFormSettingsConfig($integration->handle, $form);
    $values = [];
    $collect = function(array $nodes) use (&$collect, &$values): void {
        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }

            if (($node['$field'] ?? null) === 'lightswitch' && isset($node['name'])) {
                // The builder seeds schema defaults first, then saved values on top.
                $values[$node['name']] = $config['defaultValues'][$node['name']] ?? $node['defaultValue'] ?? false;
            }

            $collect($node['schema'] ?? $node['children'] ?? []);
        }
    };
    $collect($config['schema']);

    return $values;
}

function switchDefaultsRuntime(IntegrationInterface $integration, array $saved): IntegrationInterface
{
    return Formie::$plugin->getIntegrations()->createIntegrationRuntime($integration, ['enabled' => true] + $saved);
}

it('shows User switches at the value a send uses when the form has not saved them', function(): void {
    $integration = switchDefaultsIntegration(UserIntegration::class);

    try {
        $values = switchDefaultsBuilderValues($integration, []);
        $runtime = switchDefaultsRuntime($integration, []);

        expect($runtime->sendActivationEmail)->toBeTrue()
            ->and($values['sendActivationEmail'])->toBe($runtime->sendActivationEmail)
            ->and($runtime->updateSearchIndexes)->toBeTrue()
            ->and($values['updateSearchIndexes'])->toBe($runtime->updateSearchIndexes)
            ->and($values['activateUser'])->toBe($runtime->activateUser);
    } finally {
        Formie::$plugin->getIntegrations()->deleteIntegrationById($integration->id);
    }
});

it('shows User switches the form switched off', function(): void {
    $integration = switchDefaultsIntegration(UserIntegration::class);

    try {
        $saved = ['sendActivationEmail' => false, 'updateSearchIndexes' => false];
        $values = switchDefaultsBuilderValues($integration, $saved);
        $runtime = switchDefaultsRuntime($integration, $saved);

        expect($runtime->sendActivationEmail)->toBeFalse()
            ->and($values['sendActivationEmail'])->toBeFalse()
            ->and($runtime->updateSearchIndexes)->toBeFalse()
            ->and($values['updateSearchIndexes'])->toBeFalse();
    } finally {
        Formie::$plugin->getIntegrations()->deleteIntegrationById($integration->id);
    }
});

it('shows the Entries search index switch at the value a send uses when the form has not saved it', function(): void {
    $integration = switchDefaultsIntegration(EntryIntegration::class);

    try {
        $values = switchDefaultsBuilderValues($integration, []);
        $runtime = switchDefaultsRuntime($integration, []);

        expect($runtime->updateSearchIndexes)->toBeTrue()
            ->and($values['updateSearchIndexes'])->toBe($runtime->updateSearchIndexes);
    } finally {
        Formie::$plugin->getIntegrations()->deleteIntegrationById($integration->id);
    }
});
