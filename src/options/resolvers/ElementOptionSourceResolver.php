<?php
namespace verbb\formie\options\resolvers;

use verbb\formie\models\OptionSource;
use verbb\formie\options\ElementOptionSourceHelper;
use verbb\formie\options\OptionList;
use verbb\formie\options\OptionSourceContext;
use verbb\formie\options\OptionSourceFieldInterface;
use verbb\formie\options\OptionSourceResolverInterface;
use verbb\formie\options\OptionSourceValidationMode;

class ElementOptionSourceResolver implements OptionSourceResolverInterface
{
    // Public Methods
    // =========================================================================

    public function supports(OptionSource $source): bool
    {
        return $source->type === 'element'
            && ElementOptionSourceHelper::getProviderFieldClass((string)$source->provider) !== null;
    }

    public function resolve(OptionSourceFieldInterface $field, OptionSourceContext $context): OptionList
    {
        $source = $field->getOptionSource();

        if (!$source?->provider) {
            return OptionList::error('Missing element option source configuration.');
        }

        return ElementOptionSourceHelper::resolveOptions($source->provider, $source->params);
    }

    public function validationMode(OptionSource $source): string
    {
        return OptionSourceValidationMode::STRICT;
    }
}
