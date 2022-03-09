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

    public function getGlobalVariables($key)
    {
        return $this->globalVariables[$key] ?? [];
    }

    public function setGlobalVariables($key, $values): void
    {
        $this->globalVariables[$key] = array_merge($this->getGlobalVariables($key), $values);
    }

    public function getFieldVariables($key)
    {
        return $this->fieldVariables[$key] ?? [];
    }

    public function setFieldVariables($key, $values): void
    {
        $this->fieldVariables[$key] = array_merge($this->getFieldVariables($key), $values);
    }

    public function getVariables($key): array
    {
        return array_merge($this->getGlobalVariables($key), $this->getFieldVariables($key));
    }

    public function getElementFieldElements($key)
    {
        return $this->elementFieldElements[$key] ?? [];
    }

    public function setElementFieldElements($key, $value): void
    {
        $this->elementFieldElements[$key] = $value;
    }

}
