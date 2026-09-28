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
        $class = $integration::class;
        if (isset(self::$_settingAttributesByClass[$class])) {
            return self::$_settingAttributesByClass[$class];
        }

        $attributes = [];
        foreach ((new ReflectionObject($integration))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic() && !$property->isReadOnly() && $property->getAttributes(FormIntegrationSetting::class)) {
                $attributes[] = $property->getName();
            }
        }

        return self::$_settingAttributesByClass[$class] = $attributes;
    }

    public static function filterSettings(IntegrationInterface $integration, array $settings): array
    {
        // Never consult provider overrides, validation rules or UI schema for authority.
        return array_intersect_key($settings, array_fill_keys(array_merge(['enabled', 'execution'], self::settingAttributes($integration)), true));
    }

    public static function validateSchema(IntegrationInterface $integration, array $compiledSchema): void
    {
        $allowed = array_fill_keys(array_merge(['enabled', 'execution'], self::settingAttributes($integration)), true);
        foreach ($compiledSchema['fieldEntries'] ?? [] as $entry) {
            $path = (string)($entry['path'] ?? '');
            $root = strtok($path, '.*[') ?: '';
            if ($root !== '' && !isset($allowed[$root])) {
                throw new InvalidArgumentException(sprintf(
                    '%s form settings schema targets unannotated property “%s”. Add #[FormIntegrationSetting] or remove the persisted schema field.',
                    $integration::class,
                    $root,
                ));
            }
        }
    }

    public static function fromSettings(IntegrationInterface $integration, array $settings, ?int $formId = null, ?string $formHandle = null): self
    {
        $settings = self::filterSettings($integration, $settings);
        $execution = $settings['execution'] ?? 'queued';
        if (!in_array($execution, ['synchronous', 'queued'], true)) {
            throw new InvalidArgumentException('Invalid integration execution lane.');
        }
        $enabled = (bool)($settings['enabled'] ?? false);
        unset($settings['enabled'], $settings['execution']);

        return new self($integration, $enabled, $execution, $settings, $formId, $formHandle);
    }


    // Properties
    // =========================================================================

    public readonly IntegrationInterface $integration;
    public readonly bool $enabled;
    public readonly string $execution;
    public readonly array $settings;
    public readonly ?int $formId;
    public readonly ?string $formHandle;

    private static array $_settingAttributesByClass = [];


    // Public Methods
    // =========================================================================

    public function __construct(IntegrationInterface $integration, bool $enabled, string $execution, array $settings, ?int $formId = null, ?string $formHandle = null)
    {
        $this->integration = $integration;
        $this->enabled = $enabled;
        $this->execution = $execution;
        $this->settings = $settings;
        $this->formId = $formId;
        $this->formHandle = $formHandle;
    }

    public function createRuntime(): IntegrationInterface
    {
        $runtime = clone $this->integration;
        $runtime->enabled = $this->enabled;
        // Recheck at hydration even for bindings constructed directly by extensions.
        $settings = array_intersect_key($this->settings, array_fill_keys(self::settingAttributes($runtime), true));
        $runtime->setAttributes($settings, false);
        $runtime->setScenario(Integration::SCENARIO_FORM);

        return $runtime;
    }

    public function toSettings(): array
    {
        return ['enabled' => $this->enabled, 'execution' => $this->execution] + $this->settings;
    }

    public function validate(): array
    {
        $runtime = $this->createRuntime();
        $runtime->validate(self::settingAttributes($runtime));

        return $runtime->getErrors();
    }
}
