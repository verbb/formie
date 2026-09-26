<?php
namespace verbb\formie\base;

trait FieldBrowserConditionTrait
{
    // Public Methods
    // =========================================================================

    public function getBrowserConditions(): array
    {
        return $this->conditions()->toArray();
    }

    public function getConditionsJson(): ?string
    {
        return $this->conditions()->toJson();
    }
}
