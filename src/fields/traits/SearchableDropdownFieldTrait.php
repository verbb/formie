<?php
namespace verbb\formie\fields\traits;

use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\ClientModule;

use Craft;

use GraphQL\Type\Definition\Type;

trait SearchableDropdownFieldTrait
{
    // Properties
    // =========================================================================

    public bool $useSearchable = false;


    // Protected Methods
    // =========================================================================

    protected function defineSearchableDropdownSettingSchema(array $config = []): array
    {
        return SchemaHelper::lightswitchField(array_merge([
            'label' => Craft::t('formie', 'Use Searchable Dropdown'),
            'instructions' => Craft::t('formie', 'Allow users to filter options by typing. Recommended for long option lists.'),
            'name' => 'useSearchable',
        ], $config));
    }

    protected function defineSearchableDropdownGqlType(): array
    {
        return [
            'useSearchable' => [
                'name' => 'useSearchable',
                'type' => Type::boolean(),
            ],
        ];
    }

    protected function defineSearchableDropdownRules(): array
    {
        return [
            ['useSearchable', 'boolean'],
        ];
    }

    protected function shouldEnableSearchableDropdown(): bool
    {
        if (!(bool)$this->useSearchable) {
            return false;
        }

        if (property_exists($this, 'displayType')) {
            return (string)$this->displayType === 'dropdown';
        }

        return true;
    }

    protected function getSearchableDropdownClientModuleConfig(): array
    {
        $placeholder = null;

        if (property_exists($this, 'placeholder') && is_string($this->placeholder) && $this->placeholder !== '') {
            $placeholder = Craft::t('formie', $this->placeholder);
        }

        return [
            'multiple' => (bool)($this->multi ?? false),
            'placeholder' => $placeholder,
        ];
    }

    protected function defineSearchableDropdownClientModules(): array
    {
        if (!$this->shouldEnableSearchableDropdown()) {
            return [];
        }

        return [
            new ClientModule([
                'id' => 'combobox',
                'config' => $this->getSearchableDropdownClientModuleConfig(),
            ]),
        ];
    }

    protected function applySearchableDropdownSelectAttributes(array $attributes): array
    {
        if (!$this->shouldEnableSearchableDropdown()) {
            return $attributes;
        }

        $attributes['data-formie-combobox-input'] = true;

        return $attributes;
    }
}
