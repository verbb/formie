<?php
namespace verbb\formie\models;

use verbb\formie\elements\Form;

final readonly class ImportResult
{
    // Public Methods
    // =========================================================================

    public function __construct(
        public Form $form,
        public array $warnings,
        public array $missingTypes,
        public array $dependencies,
        public array $changes,
        public array $remaps,
    ) {
    }
}
