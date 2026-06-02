<?php
namespace verbb\formie\fields\definitions;

use yii\base\BaseObject;

/**
 * Describes the reference selectors and nested-reference policy a field exposes.
 */
class FieldReferences extends BaseObject
{
    // Static Methods
    // =========================================================================

    public static function make(): self
    {
        return new self();
    }


    // Properties
    // =========================================================================

    public bool $allowPrimary = true;
    public ?string $primaryCondition = null;
    public ?string $primaryTokenSuffix = null;
    public bool $allowNested = false;
    public string $nestedMode = 'none';
    public array $selectors = [];


    // Public Methods
    // =========================================================================

    public function withPrimary(bool $allowPrimary = true): self
    {
        $this->allowPrimary = $allowPrimary;

        return $this;
    }

    public function withPrimaryCondition(?string $condition): self
    {
        $this->primaryCondition = $condition;

        return $this;
    }

    public function withPrimaryTokenSuffix(?string $suffix): self
    {
        $this->primaryTokenSuffix = $suffix;

        return $this;
    }

    public function withNested(bool $allowNested = true, string $nestedMode = 'childrenOnly'): self
    {
        $this->allowNested = $allowNested;
        $this->nestedMode = $nestedMode;

        return $this;
    }

    public function addSelector(FieldReferenceSelector|array $selector): self
    {
        if ($selector instanceof FieldReferenceSelector) {
            $this->selectors[] = $selector;

            return $this;
        }

        $this->selectors[] = FieldReferenceSelector::fromArray($selector);

        return $this;
    }

    public function withSelectors(array $selectors): self
    {
        $this->selectors = [];

        foreach ($selectors as $selector) {
            $this->addSelector($selector);
        }

        return $this;
    }

    public function toConfigArray(): array
    {
        return [
            'allowPrimary' => $this->allowPrimary,
            'primaryCondition' => $this->primaryCondition,
            'primaryTokenSuffix' => $this->primaryTokenSuffix,
            'allowNested' => $this->allowNested,
            'nestedMode' => $this->nestedMode,
            'selectors' => array_values(array_map(static function(FieldReferenceSelector $selector) {
                return $selector->toArray();
            }, $this->selectors)),
        ];
    }
}
