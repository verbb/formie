<?php
namespace verbb\formie\fields\traits;

use verbb\formie\helpers\SchemaHelper;

use Craft;

use GraphQL\Type\Definition\Type;

trait SearchableDropdownLimitFieldTrait
{
    // Properties
    // =========================================================================

    public int|float|null $searchableOptionLimit = 100;


    // Protected Methods
    // =========================================================================

    protected function defineSearchableDropdownLimitSettingSchema(array $config = []): array
    {
        return SchemaHelper::numberField(array_merge([
            'label' => Craft::t('formie', 'Maximum Displayed Options'),
            'instructions' => Craft::t('formie', 'The maximum number of options shown in the dropdown or search results. Searches include all options. Leave blank to show all options.'),
            'name' => 'searchableOptionLimit',
            'min' => 1,
            'step' => 1,
            'if' => 'useSearchable',
        ], $config));
    }

    protected function defineSearchableDropdownLimitRules(): array
    {
        return [
            ['searchableOptionLimit', 'integer', 'min' => 1],
        ];
    }

    protected function defineSearchableDropdownLimitGqlType(): array
    {
        return [
            'searchableOptionLimit' => [
                'name' => 'searchableOptionLimit',
                'type' => Type::int(),
            ],
        ];
    }
}
