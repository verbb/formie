<?php
namespace verbb\formie\options;

use verbb\formie\models\OptionSource;

interface OptionSourceFieldInterface
{
    // Public Methods
    // =========================================================================

    public function getOptionsMode(): string;
    public function getOptionSource(): ?OptionSource;
    public function options(): array;
}
