<?php
namespace verbb\formie\options\resolvers;

use verbb\formie\fields\Recipients;
use verbb\formie\models\OptionSource;
use verbb\formie\options\OptionList;
use verbb\formie\options\OptionSourceContext;
use verbb\formie\options\OptionSourceFieldInterface;
use verbb\formie\options\OptionSourceProviderHelper;
use verbb\formie\options\OptionSourceResolverInterface;
use verbb\formie\options\OptionSourceValidationMode;

class RegisteredOptionSourceResolver implements OptionSourceResolverInterface
{
    // Public Methods
    // =========================================================================

    public function supports(OptionSource $source): bool
    {
        return $source->type === 'provider'
            && $source->provider
            && OptionSourceProviderHelper::providerExists((string)$source->provider);
    }

    public function resolve(OptionSourceFieldInterface $field, OptionSourceContext $context): OptionList
    {
        $source = $field->getOptionSource();

        if (!$source?->provider) {
            return OptionList::error('Missing registered option source configuration.');
        }

        $usage = $field instanceof Recipients
            ? OptionSourceProviderHelper::USAGE_RECIPIENTS
            : OptionSourceProviderHelper::USAGE_OPTIONS;

        return OptionSourceProviderHelper::resolveOptions(
            (string)$source->provider,
            $source->params,
            $context,
            $usage,
        );
    }

    public function validationMode(OptionSource $source): string
    {
        return OptionSourceValidationMode::STRICT;
    }
}
