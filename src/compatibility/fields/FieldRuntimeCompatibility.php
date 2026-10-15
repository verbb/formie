<?php
namespace verbb\formie\compatibility\fields;

use verbb\formie\elements\Submission;
use verbb\formie\fields\definitions\FieldValueType;

use Craft;
use craft\base\ElementInterface;

use ReflectionProperty;

trait FieldRuntimeCompatibility
{
    // Public Methods
    // =========================================================================

    public function __clone()
    {
        parent::__clone();
        $this->initializeLegacySettingAliases(false);
    }

    public function __get($name)
    {
        $canonical = FieldConfigNormalizer::settingAliases(static::class)[$name] ?? null;

        if ($canonical !== null) {
            Craft::$app->getDeprecator()->log(static::class . '::' . $name, "Field `{$name}` has been deprecated. Use `{$canonical}` instead.");
            return $this->$canonical;
        }

        return parent::__get($name);
    }

    public function __set($name, $value)
    {
        $canonical = FieldConfigNormalizer::settingAliases(static::class)[$name] ?? null;

        if ($canonical !== null) {
            Craft::$app->getDeprecator()->log(static::class . '::' . $name, "Field `{$name}` has been deprecated. Use `{$canonical}` instead.");
            $this->$canonical = $value;
            return;
        }

        parent::__set($name, $value);
    }

    public function __isset($name): bool
    {
        $canonical = FieldConfigNormalizer::settingAliases(static::class)[$name] ?? null;
        return $canonical !== null ? isset($this->$canonical) : parent::__isset($name);
    }

    public function canGetProperty($name, $checkVars = true, $checkBehaviors = true): bool
    {
        return isset(FieldConfigNormalizer::settingAliases(static::class)[$name]) || parent::canGetProperty($name, $checkVars, $checkBehaviors);
    }

    public function canSetProperty($name, $checkVars = true, $checkBehaviors = true): bool
    {
        return isset(FieldConfigNormalizer::settingAliases(static::class)[$name]) || parent::canSetProperty($name, $checkVars, $checkBehaviors);
    }

    public function getSyncId(): ?int
    {
        Craft::$app->getDeprecator()->log(static::class . '::syncId', 'The `syncId` field property has been deprecated. Use `definitionId` and `isSynced` instead.');

        return $this->getIsSynced() ? $this->definitionId : null;
    }

    public function setSyncId(?int $value): void
    {
        Craft::$app->getDeprecator()->log(static::class . '::syncId', 'The `syncId` field property has been deprecated. Use `definitionId` and `isSynced` instead.');

        if ($value) {
            $this->definitionId = $value;
            $this->isSynced = true;
        }
    }


    // Protected Methods
    // =========================================================================

    protected function initializeLegacySettingAliases(bool $useLegacyDefaults = true): void
    {
        foreach (FieldConfigNormalizer::settingAliases(static::class) as $legacy => $canonical) {
            if (!property_exists($this, $legacy)) {
                continue;
            }

            $legacyProperty = new ReflectionProperty($this, $legacy);

            if (!$legacyProperty->isPublic() || $legacyProperty->isStatic()) {
                continue;
            }

            $legacyOwner = $legacyProperty->getDeclaringClass()->getName();
            $canonicalOwner = (new ReflectionProperty($this, $canonical))->getDeclaringClass()->getName();
            $value = $this->$canonical;

            if ($useLegacyDefaults && $legacyProperty->isInitialized($this) && is_subclass_of($legacyOwner, $canonicalOwner)) {
                Craft::$app->getDeprecator()->log(static::class . '::' . $legacy, "Field `{$legacy}` has been deprecated. Declare `{$canonical}` instead.");
                $value = $this->$legacy;
            }

            // Declared public properties bypass magic access, including array mutations.
            // A fresh reference container also detaches clones from the original field.
            $this->$canonical = & $value;
            $this->$legacy = & $this->$canonical;
            unset($value);
        }
    }

    protected function resolveValueAsData(mixed $value, ?ElementInterface $element = null): mixed
    {
        return $this->callLegacyFieldMethod('defineValueAsJson', 'defineValueAsData', [$value, $element], fn() => $this->defineValueAsData($value, $element));
    }

    protected function callLegacyFieldMethod(string $legacyMethod, string $canonicalMethod, array $arguments, callable $fallback): mixed
    {
        if (FieldCompatibility::prefersLegacyMethod($this, $legacyMethod, $canonicalMethod)) {
            Craft::$app->getDeprecator()->log(static::class . '::' . $legacyMethod, "Field `{$legacyMethod}()` has been deprecated. Use `{$canonicalMethod}()` instead.");
            return FieldCompatibility::withLegacyCall($this, $legacyMethod, fn() => $this->$legacyMethod(...$arguments));
        }

        return $fallback();
    }

    protected function resolveLegacyReferenceValue(mixed $value, ?ElementInterface $element, callable $fallback): mixed
    {
        $notification = FieldCompatibility::referenceNotification($this);

        // Old variable hooks require a real notification. Ordinary references do
        // not invent one, so the modern hook remains the fallback outside email.
        if (!$element instanceof Submission || !$notification) {
            return $fallback();
        }

        foreach (['getValueForVariable', 'getValueForVariableRaw', 'defineValueForVariable', 'defineValueForVariableRaw'] as $legacyMethod) {
            $canonicalMethod = str_starts_with($legacyMethod, 'get') ? 'getValueForReference' : 'defineValueForReference';

            if (FieldCompatibility::prefersLegacyMethod($this, $legacyMethod, $canonicalMethod)) {
                return $this->callLegacyFieldMethod($legacyMethod, $canonicalMethod, [$value, $element, $notification], $fallback);
            }
        }

        return $fallback();
    }

    protected function legacyValueType(): FieldValueType
    {
        $type = ltrim(str_replace('|null', '', static::phpType()), '?\\');
        $class = $this->defineValueClass();

        if ($class || !in_array($type, ['mixed', 'string'], true)) {
            Craft::$app->getDeprecator()->log(static::class . '::valueType', 'Declare defineValueType() for the post-normalization runtime value. Legacy phpType()/defineValueClass() is deprecated.');
        }

        if ($class && class_exists($class)) {
            return FieldValueType::object($class);
        }

        return match ($type) {
            'mixed', 'int', 'float', 'int|float' => FieldValueType::storageSafe(),
            'bool', 'boolean' => FieldValueType::boolean(),
            'array' => FieldValueType::array(),
            default => class_exists($type)
                ? FieldValueType::object($type)
                : FieldValueType::string(),
        };
    }

}
