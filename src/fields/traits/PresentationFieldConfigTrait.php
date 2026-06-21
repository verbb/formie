<?php
namespace verbb\formie\fields\traits;

use verbb\formie\base\FieldInterface;
use verbb\formie\fields\Checkboxes;
use verbb\formie\fields\Dropdown;
use verbb\formie\fields\Radio;

use ReflectionClass;
use ReflectionProperty;

trait PresentationFieldConfigTrait
{
    protected function resolvePresentationField(string $displayType, array $config): ?FieldInterface
    {
        $fieldClass = $this->definePresentationFieldClassMap()[$displayType] ?? null;

        if (!$fieldClass) {
            return null;
        }

        return $this->createPresentationField($fieldClass, $config);
    }

    protected function createPresentationField(string $fieldClass, array $config): FieldInterface
    {
        return new $fieldClass($this->filterPresentationFieldConfig($fieldClass, $config));
    }

    protected function definePresentationFieldClassMap(): array
    {
        return $this->defaultPresentationFieldClassMap();
    }

    protected function defaultPresentationFieldClassMap(): array
    {
        return [
            'dropdown' => Dropdown::class,
            'radio' => Radio::class,
            'checkboxes' => Checkboxes::class,
        ];
    }

    protected function filterPresentationFieldConfig(string $fieldClass, array $config): array
    {
        static $propertyNamesByClass = [];

        if (!isset($propertyNamesByClass[$fieldClass])) {
            $propertyNamesByClass[$fieldClass] = $this->getPresentationFieldPublicPropertyNames($fieldClass);
        }

        return array_intersect_key($config, array_flip($propertyNamesByClass[$fieldClass]));
    }

    protected function getPresentationFieldPublicPropertyNames(string $class): array
    {
        $names = [];

        for ($reflection = new ReflectionClass($class); $reflection; $reflection = $reflection->getParentClass()) {
            foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
                if (!$property->isStatic()) {
                    $names[] = $property->getName();
                }
            }
        }

        return array_values(array_unique($names));
    }
}
