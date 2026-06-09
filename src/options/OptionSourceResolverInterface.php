<?php
namespace verbb\formie\options;

use verbb\formie\models\OptionSource;

interface OptionSourceResolverInterface
{
    // Public Methods
    // =========================================================================
    
    public function supports(OptionSource $source): bool;
    public function resolve(OptionSourceFieldInterface $field, OptionSourceContext $context): OptionList;
    public function validationMode(OptionSource $source): string;
}
