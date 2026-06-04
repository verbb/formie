<?php
namespace verbb\formie\fields\traits;

use verbb\formie\helpers\HtmlAutocomplete;
use verbb\formie\helpers\SchemaHelper;

use Craft;

use GraphQL\Type\Definition\Type;

trait AutocompleteFieldTrait
{
    // Properties
    // =========================================================================

    public ?string $autocomplete = null;


    // Protected Methods
    // =========================================================================

    protected function defineAutocompleteSettingSchema(array $config = []): array
    {
        return SchemaHelper::autocompleteField($config);
    }

    protected function defineAutocompleteGqlType(): array
    {
        return [
            'autocomplete' => [
                'name' => 'autocomplete',
                'type' => Type::string(),
            ],
        ];
    }

    protected function defineAutocompleteRules(): array
    {
        return [
            ['autocomplete', 'string', 'max' => 255],
            ['autocomplete', function(string $attribute): void {
                if (!HtmlAutocomplete::isValid($this->{$attribute})) {
                    $this->addError($attribute, Craft::t('formie', 'Invalid autocomplete value.'));
                }
            }, 'skipOnEmpty' => true],
        ];
    }

    protected function getAutocompleteCoreAttribute(): ?string
    {
        $value = is_string($this->autocomplete) ? trim($this->autocomplete) : '';

        return $value !== '' ? $value : null;
    }
}
