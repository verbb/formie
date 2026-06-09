<?php
namespace verbb\formie\options\resolvers;

use verbb\formie\models\OptionSource;
use verbb\formie\options\IntegrationOptionSourceHelper;
use verbb\formie\options\OptionList;
use verbb\formie\options\OptionSourceContext;
use verbb\formie\options\OptionSourceFieldInterface;
use verbb\formie\options\OptionSourceResolverInterface;
use verbb\formie\options\OptionSourceValidationMode;

class IntegrationOptionSourceResolver implements OptionSourceResolverInterface
{
    // Public Methods
    // =========================================================================

    public function supports(OptionSource $source): bool
    {
        return $source->type === 'integration'
            && $source->provider
            && IntegrationOptionSourceHelper::providerExists((string)$source->provider);
    }

    public function resolve(OptionSourceFieldInterface $field, OptionSourceContext $context): OptionList
    {
        $source = $field->getOptionSource();

        if (!$source?->provider) {
            return OptionList::error('Missing integration option source configuration.');
        }

        return IntegrationOptionSourceHelper::resolveOptions($source->provider, $source->params);
    }

    public function validationMode(OptionSource $source): string
    {
        return OptionSourceValidationMode::STRICT;
    }
}
