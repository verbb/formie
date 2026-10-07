<?php
namespace verbb\formie\compatibility\fields;

use verbb\formie\fields\definitions\FieldValueType;

use Craft;

use ReflectionMethod;

trait FieldRuntimeCompatibility
{
    // Static Methods
    // =========================================================================

    private static function _hasLegacyStaticMethodOverride(string $method): bool
    {
        $reflection = new ReflectionMethod(static::class, $method);

        return $reflection->getDeclaringClass()->getName() !== self::class;
    }


    // Properties
    // =========================================================================

    private bool $_projectingLegacyData = false;


    // Public Methods
    // =========================================================================

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


    // Private Methods
    // =========================================================================

    private function _hasLegacyFieldMethodOverride(string $method): bool
    {
        $reflection = new ReflectionMethod(static::class, $method);

        return $reflection->getDeclaringClass()->getName() !== self::class;
    }
}
