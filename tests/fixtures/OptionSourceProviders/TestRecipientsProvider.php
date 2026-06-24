<?php

declare(strict_types=1);

namespace Tests\fixtures\OptionSourceProviders;

use verbb\formie\options\OptionList;
use verbb\formie\options\OptionSourceContext;
use verbb\formie\options\OptionSourceProviderHelper;
use verbb\formie\options\OptionSourceProviderInterface;

class TestRecipientsProvider implements OptionSourceProviderInterface
{
    public static function handle(): string
    {
        return 'test-recipients';
    }

    public static function displayName(): string
    {
        return 'Test Recipients';
    }

    public static function usages(): array
    {
        return [OptionSourceProviderHelper::USAGE_RECIPIENTS];
    }

    public function getBuilderConfig(array $params = []): array
    {
        return [
            'paramFields' => [],
            'defaults' => [],
        ];
    }

    public function resolveOptions(array $params, OptionSourceContext $context): OptionList
    {
        return OptionList::fromRows([
            ['label' => 'Sales', 'value' => 'sales@example.com'],
            ['label' => 'Support', 'value' => 'support@example.com'],
            ['label' => 'Invalid', 'value' => 'not-an-email'],
        ]);
    }
}
