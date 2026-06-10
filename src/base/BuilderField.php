<?php
namespace verbb\formie\base;

use verbb\formie\helpers\StringHelper;

use Craft;
use craft\models\GqlSchema;

abstract class BuilderField extends CosmeticField implements BuilderFieldInterface
{
    // Public Methods
    // =========================================================================

    public function getIsBuilderField(): bool
    {
        return true;
    }

    public static function getFrontEndInputTemplatePath(): string
    {
        return '';
    }

    public static function getInputTemplatePath(): string
    {
        return '';
    }

    public function includeInGqlSchema(GqlSchema $schema): bool
    {
        return false;
    }

    public function hasConditions(): bool
    {
        return false;
    }

    public function defineFormBuilderAppearanceSchema(): array
    {
        return [];
    }

    public function defineFormBuilderAdvancedSchema(): array
    {
        return [];
    }

    public function defineFormBuilderConditionsSchema(): array
    {
        return [];
    }

    public function modifyFieldSettings(array $settings): array
    {
        if (!$this->id && !$this->fieldId) {
            if (empty($settings['label'])) {
                $settings['label'] = StringHelper::appendRandomString($this->getBuilderIdentityLabelPrefix(), 15);
            }

            if (empty($settings['handle'])) {
                $settings['handle'] = StringHelper::appendRandomString($this->getBuilderIdentityHandlePrefix(), 15);
            }
        }

        return parent::modifyFieldSettings($settings);
    }

    public function afterCreateField(array $data): void
    {
        $this->label = $this->label ?: StringHelper::appendRandomString($this->getBuilderIdentityLabelPrefix(), 15);
        $this->handle = $this->handle ?: StringHelper::appendRandomString($this->getBuilderIdentityHandlePrefix(), 15);

        parent::afterCreateField($data);
    }


    // Protected Methods
    // =========================================================================

    protected function getBuilderIdentityLabelPrefix(): string
    {
        return static::displayName() . ' ';
    }

    protected function getBuilderIdentityHandlePrefix(): string
    {
        return StringHelper::toCamelCase(static::className());
    }
}
