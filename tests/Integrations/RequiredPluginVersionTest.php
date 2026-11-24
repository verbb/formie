<?php

declare(strict_types=1);

use craft\errors\MissingComponentException;
use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\events\RegisterIntegrationsEvent;
use verbb\formie\fields\SingleLineText;
use verbb\formie\integrations\automations\Zapier;
use verbb\formie\services\Integrations;
use yii\base\Event;

class RequiredPluginAnyVersionIntegration extends Zapier
{
    public static function getRequiredPlugins(): array
    {
        return ['formie'];
    }
}

class RequiredPluginMinimumVersionIntegration extends Zapier
{
    public static function getRequiredPlugins(): array
    {
        return [['handle' => 'formie', 'version' => '99.0.0']];
    }
}

class RequiredPluginAnyVersionField extends SingleLineText
{
    public static function getRequiredPlugins(): array
    {
        return [['handle' => 'formie']];
    }
}

class RequiredPluginMinimumVersionField extends SingleLineText
{
    public static function getRequiredPlugins(): array
    {
        return [['handle' => 'formie', 'version' => '99.0.0']];
    }
}

// A plugin installed from a branch without an alias reports its version as `dev-main`, which sorts below any number.
function withFormieVersion(string $version, callable $callback): void
{
    $originalVersion = Formie::$plugin->getVersion();
    $handler = static function(RegisterIntegrationsEvent $event): void {
        $event->automations[] = RequiredPluginAnyVersionIntegration::class;
        $event->automations[] = RequiredPluginMinimumVersionIntegration::class;
    };
    $resetCaches = new ReflectionMethod(Formie::$plugin->getIntegrations(), '_resetIntegrationCaches');

    Formie::$plugin->setVersion($version);
    Event::on(Integrations::class, Integrations::EVENT_REGISTER_INTEGRATIONS, $handler);
    $resetCaches->invoke(Formie::$plugin->getIntegrations());

    try {
        $callback();
    } finally {
        Event::off(Integrations::class, Integrations::EVENT_REGISTER_INTEGRATIONS, $handler);
        Formie::$plugin->setVersion($originalVersion);
        $resetCaches->invoke(Formie::$plugin->getIntegrations());
    }
}

it('keeps integrations without a minimum version when the required plugin is a branch install', function(string $version): void {
    withFormieVersion($version, function(): void {
        $types = Formie::$plugin->getIntegrations()->getIntegrationTypes(Integration::TYPE_AUTOMATION);

        expect($types)->toContain(RequiredPluginAnyVersionIntegration::class)
            ->and($types)->not->toContain(RequiredPluginMinimumVersionIntegration::class);
    });
})->with(['dev-main', '5.x-dev', '4.1.0']);

it('accepts fields without a minimum version when the required plugin is a branch install', function(string $version): void {
    withFormieVersion($version, function(): void {
        $fields = Formie::$plugin->getFields();

        expect($fields->checkRequiredPlugin(new RequiredPluginAnyVersionField()))->toBeTrue()
            ->and(fn() => $fields->checkRequiredPlugin(new RequiredPluginMinimumVersionField()))->toThrow(MissingComponentException::class);
    });
})->with(['dev-main', '5.x-dev', '4.1.0']);
