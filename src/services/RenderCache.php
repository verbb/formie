<?php
namespace verbb\formie\services;

use craft\base\Component;

class RenderCache extends Component
{
    // Properties
    // =========================================================================

    public array $globalVariables = [];
    public array $fieldVariables = [];
    public array $elementFieldElements = [];


    // Public Methods
    // =========================================================================

    public function getGlobalVariables(string $key): mixed
    {
        return $this->globalVariables[$key] ?? [];
    }

    public function setGlobalVariables(string $key, array $values): void
    {
        $this->globalVariables[$key] = array_merge($this->getGlobalVariables($key), $values);
    }

    public function getFieldVariables(string $key): mixed
    {
        return $this->fieldVariables[$key] ?? [];
    }

    public function setFieldVariables(string $key, array $values): void
    {
        $this->fieldVariables[$key] = array_merge($this->getFieldVariables($key), $values);
    }

    public function getVariables(string $key): array
    {
        return array_merge($this->getGlobalVariables($key), $this->getFieldVariables($key));
    }

    public function getElementFieldElements(string $key): mixed
    {
        return $this->elementFieldElements[$key] ?? [];
    }

    public function setElementFieldElements(string $key, mixed $value): void
    {
        $this->elementFieldElements[$key] = $value;
    }

}
