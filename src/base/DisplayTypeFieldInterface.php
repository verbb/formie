<?php
namespace verbb\formie\base;

interface DisplayTypeFieldInterface
{
    /**
     * Returns a real Formie field instance matching the current presentation mode.
     *
     * Used by display-type wrapper fields (Quiz, Survey, Recipients, element fields, etc.)
     * so shared input templates run against a genuine field class with the correct
     * properties, slot tags, and client modules.
     */
    public function getDisplayTypeField(): ?FieldInterface;

    /**
     * Returns the active presentation mode key (e.g. `radio`, `dropdown`, `hidden`).
     */
    public function getPresentationDisplayType(): string;
}
