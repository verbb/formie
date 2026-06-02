<?php
namespace verbb\formie\cache;

use verbb\formie\base\FieldInterface;

use craft\base\Component;

class RenderCache extends Component
{
    // Properties
    // =========================================================================

    private array $_globalVariables = [];
    private array $_fieldVariables = [];
    private array $_resolvedVariables = [];
    private array $_summaryVariables = [];
    private array $_fieldReferenceIndex = [];
    private array $_elementFieldElements = [];


    // Public Methods
    // =========================================================================

    public function reset(): void
    {
        $this->_globalVariables = [];
        $this->_fieldVariables = [];
        $this->_resolvedVariables = [];
        $this->_summaryVariables = [];
        $this->_fieldReferenceIndex = [];
        $this->_elementFieldElements = [];
    }

    public function getGlobalVariables(string $key): array
    {
        return $this->_globalVariables[$key] ?? [];
    }

    public function setGlobalVariables(string $key, array $values): void
    {
        $this->_globalVariables[$key] = array_merge($this->getGlobalVariables($key), $values);
        unset($this->_resolvedVariables[$key]);
    }

    public function getFieldVariables(string $key): array
    {
        return $this->_fieldVariables[$key] ?? [];
    }

    public function setFieldVariables(string $key, array $values): void
    {
        $this->_fieldVariables[$key] = array_merge($this->getFieldVariables($key), $values);
        unset($this->_resolvedVariables[$key]);
    }

    public function getVariables(string $key): array
    {
        return array_merge($this->getGlobalVariables($key), $this->getFieldVariables($key));
    }

    public function getResolvedVariables(string $key): ?array
    {
        return $this->_resolvedVariables[$key] ?? null;
    }

    public function setResolvedVariables(string $key, array $values): void
    {
        $this->_resolvedVariables[$key] = $values;
    }

    public function getSummaryVariables(string $key): ?array
    {
        return $this->_summaryVariables[$key] ?? null;
    }

    public function setSummaryVariables(string $key, array $values): void
    {
        $this->_summaryVariables[$key] = $values;
    }

    public function hasFieldReferenceIndex(string $key): bool
    {
        return array_key_exists($key, $this->_fieldReferenceIndex);
    }

    public function getFieldByReference(string $key, string $reference): ?FieldInterface
    {
        return $this->_fieldReferenceIndex[$key][$reference] ?? null;
    }

    public function setFieldReferenceIndex(string $key, array $fieldsByReference): void
    {
        $this->_fieldReferenceIndex[$key] = $fieldsByReference;
    }

    public function getElementFieldElements(string $key): mixed
    {
        return $this->_elementFieldElements[$key] ?? [];
    }

    public function setElementFieldElements(string $key, mixed $value): void
    {
        $this->_elementFieldElements[$key] = $value;
    }
}
