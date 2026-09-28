<?php
namespace verbb\formie\compatibility\fields;

use verbb\formie\fields\definitions\FieldValueType;

trait FieldRuntimeCompatibility
{
    // Static Methods
    // =========================================================================

    private static function _hasLegacyStaticMethodOverride(string $method): bool
    {
        $reflection = new \ReflectionMethod(static::class, $method);

        return $reflection->getDeclaringClass()->getName() !== self::class;
    }


    // Properties
    // =========================================================================

    private bool $_projectingLegacyData = false;


    // Protected Methods
    // =========================================================================

    protected function legacyValueType(): FieldValueType
    {
        $type = ltrim(str_replace('|null', '', static::phpType()), '?\\');
        $class = $this->defineValueClass();

        if ($class || !in_array($type, ['mixed', 'string'], true)) {
            \Craft::$app->getDeprecator()->log(static::class . '::valueType', 'Declare valueType() for the post-normalization runtime value. Legacy phpType()/defineValueClass() is deprecated.');
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
        $reflection = new \ReflectionMethod(static::class, $method);

        return $reflection->getDeclaringClass()->getName() !== self::class;
    }
}
