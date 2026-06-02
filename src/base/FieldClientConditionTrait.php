<?php
namespace verbb\formie\base;

trait FieldClientConditionTrait
{
    // Public Methods
    // =========================================================================

    public function getClientConditions(): array
    {
        return $this->conditions()->toArray();
    }

    public function getConditionsJson(): ?string
    {
        return $this->conditions()->toJson();
    }
}
