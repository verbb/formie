<?php
namespace verbb\formie\models;

use craft\base\Model;

class FieldReferenceConfig extends Model
{
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

    public static function make(): self
    {
        return new self();
    }

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

    public function withSelectors(array $selectors): self
    {
        $this->selectors = [];

        foreach ($selectors as $selector) {
            $this->addSelector($selector);
        }

        return $this;
    }

    public function addSelector(FieldReferenceSelector|array $selector): self
    {
        if ($selector instanceof FieldReferenceSelector) {
            $this->selectors[] = $selector->toArray();

            return $this;
        }

        if (is_array($selector)) {
            $this->selectors[] = $selector;
        }

        return $this;
    }

    public function toArray(array $fields = [], array $expand = [], $recursive = true): array
    {
        return [
            'allowPrimary' => $this->allowPrimary,
            'primaryCondition' => $this->primaryCondition,
            'primaryTokenSuffix' => $this->primaryTokenSuffix,
            'allowNested' => $this->allowNested,
            'nestedMode' => $this->nestedMode,
            'selectors' => $this->selectors,
        ];
    }
}
