<?php
namespace verbb\formie\options;

/**
 * Fields that expose selectable { label, value } rows through a shared resolver contract.
 *
 * Implemented by OptionsField (static / predefined dynamic), ElementField (Craft elements),
 * and Recipients (static rows with separate front-end obfuscation).
 */
interface OptionResolvableInterface
{
    // Public Methods
    // =========================================================================
    
    public function getResolvedOptions(): array;
}
