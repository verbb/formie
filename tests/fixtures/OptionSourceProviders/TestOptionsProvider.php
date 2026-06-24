<?php

declare(strict_types=1);

namespace Tests\fixtures\OptionSourceProviders;

use verbb\formie\options\OptionList;
use verbb\formie\options\OptionSourceContext;
use verbb\formie\options\OptionSourceProviderHelper;
use verbb\formie\options\OptionSourceProviderInterface;

class TestOptionsProvider implements OptionSourceProviderInterface
{
    public static function handle(): string
    {
        return 'test-options';
    }

    public static function displayName(): string
    {
        return 'Test Options';
    }

    public static function usages(): array
    {
        return [OptionSourceProviderHelper::USAGE_OPTIONS];
    }

    public function getBuilderConfig(array $params = []): array
    {
        return [
            'paramFields' => [
                [
                    'handle' => 'group',
                    'label' => 'Group',
                    'type' => 'select',
                    'options' => [
                        ['label' => 'Group A', 'value' => 'a'],
                        ['label' => 'Group B', 'value' => 'b'],
                    ],
                ],
            ],
            'defaults' => [
                'group' => 'a',
            ],
        ];
    }

    public function resolveOptions(array $params, OptionSourceContext $context): OptionList
    {
        $group = (string)($params['group'] ?? 'a');

        return OptionList::fromRows([
            ['label' => "Option {$group}-1", 'value' => "{$group}-1"],
            ['label' => "Option {$group}-2", 'value' => "{$group}-2"],
        ]);
    }
}
