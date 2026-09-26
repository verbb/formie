<?php
namespace verbb\formie\models;

use verbb\formie\attributes\FormIntegrationSetting;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;

use InvalidArgumentException;
use ReflectionObject;
use ReflectionProperty;

/** Formie owns binding state; extensions only annotate their existing properties. */
final class FormIntegration
{
    // Static Methods
    // =========================================================================

    public static function settingAttributes(IntegrationInterface $integration): array
    {
        $attributes = [];
        foreach ((new ReflectionObject($integration))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic() && !$property->isReadOnly() && $property->getAttributes(FormIntegrationSetting::class)) {
                $attributes[] = $property->getName();
            }
        }

        return $attributes;
    }

    public static function filterSettings(IntegrationInterface $integration, array $settings): array
    {
        // Never consult provider overrides, validation rules or UI schema for authority.
        return array_intersect_key($settings, array_fill_keys(array_merge(['enabled', 'execution'], self::settingAttributes($integration)), true));
    }

    public static function fromSettings(IntegrationInterface $integration, array $settings): self
    {
        $settings = self::filterSettings($integration, $settings);
        $execution = $settings['execution'] ?? 'queued';
        if (!in_array($execution, ['synchronous', 'queued'], true)) {
            throw new InvalidArgumentException('Invalid integration execution lane.');
        }
        $enabled = (bool)($settings['enabled'] ?? false);
        unset($settings['enabled'], $settings['execution']);

        return new self($enabled, $execution, $settings);
    }


    // Properties
    // =========================================================================

    public readonly bool $enabled;
    public readonly string $execution;
    public readonly array $settings;


    // Public Methods
    // =========================================================================

    public function __construct(bool $enabled, string $execution, array $settings)
    {
        $this->enabled = $enabled;
        $this->execution = $execution;
        $this->settings = $settings;
    }

    public function createRuntime(IntegrationInterface $connection): IntegrationInterface
    {
        $runtime = clone $connection;
        $runtime->enabled = $this->enabled;
        // Recheck at hydration even for bindings constructed directly by extensions.
        $settings = array_intersect_key($this->settings, array_fill_keys(self::settingAttributes($runtime), true));
        $runtime->setAttributes($settings, false);
        $runtime->setScenario(Integration::SCENARIO_FORM);

        return $runtime;
    }

    public function validate(IntegrationInterface $connection): array
    {
        $runtime = $this->createRuntime($connection);
        $runtime->validate(self::settingAttributes($runtime));

        return $runtime->getErrors();
    }
}
