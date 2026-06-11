<?php
namespace verbb\formie\fields\custom;

use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\CustomField;
use verbb\formie\fields\values\StringFieldValue;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Notification;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field as CraftField;
use craft\helpers\Cp;
use craft\helpers\ElementHelper;
use craft\helpers\Html;
use craft\helpers\Json;

use GraphQL\Type\Definition\Type;

abstract class AbstractCustomFieldAdapter implements CustomFieldAdapterInterface
{
    // Static Methods
    // =========================================================================

    public static function craftFieldClasses(): array
    {
        return [];
    }

    public static function isAvailable(): bool
    {
        $classes = static::craftFieldClasses();

        if ($classes === []) {
            return true;
        }

        foreach ($classes as $class) {
            if (class_exists($class)) {
                return true;
            }
        }

        return false;
    }


    // Public Methods
    // =========================================================================

    public function getFieldTypeDefinition(): array
    {
        return [
            'type' => static::class,
            'handle' => static::handle(),
            'label' => static::displayName(),
            'icon' => $this->getCraftFieldIconSvg() ?? $this->getSvgIcon(),
            'sourceLabel' => $this->getSourceLabel(),
            'craftFieldClasses' => static::craftFieldClasses(),
            'defaultSettings' => $this->getDefaultSettings(),
        ];
    }

    public function getDefaultSettings(): array
    {
        return [];
    }

    public function getFormBuilderSettingsSchema(CustomField $field): array
    {
        return [];
    }

    public function getFormBuilderPreviewSchema(CustomField $field): array
    {
        return [
            SchemaHelper::previewInput([
                'placeholder' => SchemaHelper::previewBind('field.customFieldAdapterSettings.placeholder', ''),
                'value' => SchemaHelper::previewBind('field.customFieldAdapterSettings.defaultValue', ''),
            ]),
        ];
    }

    public function getSettingGqlTypes(CustomField $field): array
    {
        return [];
    }

    public function getContentGqlType(CustomField $field): Type|array
    {
        return Type::string();
    }

    public function getContentGqlMutationArgumentType(CustomField $field): Type|array
    {
        return [
            'name' => $field->handle,
            'type' => Type::string(),
            'description' => $field->instructions->isEmpty() ? null : $field->instructions->toPlainText(),
        ];
    }

    public function getClientInput(CustomField $field): array
    {
        return [];
    }

    public function getClientModules(CustomField $field): array
    {
        return [];
    }

    public function getDefaultValue(CustomField $field): mixed
    {
        return $this->getSetting($field, 'defaultValue');
    }

    public function getValueClass(CustomField $field): ?string
    {
        return StringFieldValue::class;
    }

    public function normalizeValue(mixed $value, CustomField $field, ?ElementInterface $element): mixed
    {
        if (is_string($value)) {
            $decoded = Json::decodeIfJson($value);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return $value;
    }

    public function serializeValue(mixed $value, CustomField $field, ?ElementInterface $element): mixed
    {
        return $value;
    }

    public function isValueEmpty(mixed $value, CustomField $field, ?ElementInterface $element): bool
    {
        return $this->getValueAsString($value, $field, $element) === '';
    }

    public function validateValue(ElementInterface $element, CustomField $field): void
    {
    }

    public function getInputHtml(CustomField $field, Form $form, mixed $value): string
    {
        return Html::textInput($field->getHtmlName(), $this->getValueAsString($value, $field, $form->getCurrentSubmission()), [
            'id' => $field->getHtmlId($form),
            'placeholder' => Craft::t('site', $this->getPlaceholder($field)) ?: null,
            'required' => $field->required,
            'data-formie-input' => true,
            'data-formie-custom-field-input' => true,
            'data-formie-input-id' => $field->getHtmlDataId($form),
            'data-formie-input-type' => static::handle(),
        ]);
    }

    public function getCpInputHtml(CustomField $field, mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Html::textInput($field->handle, $this->getValueAsString($value, $field, $element), [
            'placeholder' => Craft::t('site', $this->getPlaceholder($field)) ?: null,
        ]);
    }

    public function getPreviewHtml(CustomField $field, mixed $value, ElementInterface $element): string
    {
        return ElementHelper::attributeHtml($this->getValueAsString($value, $field, $element));
    }

    public function getValueAsString(mixed $value, CustomField $field, ?ElementInterface $element = null): string
    {
        if ($value === null) {
            return '';
        }

        if (is_scalar($value)) {
            return trim((string)$value);
        }

        if (is_array($value)) {
            return trim(implode(', ', array_filter(array_map(static function(mixed $item): string {
                return is_scalar($item) ? (string)$item : '';
            }, $value))));
        }

        if (method_exists($value, '__toString')) {
            return trim((string)$value);
        }

        return '';
    }

    public function getValueAsArray(mixed $value, CustomField $field, ?ElementInterface $element = null): mixed
    {
        if (is_array($value)) {
            return $value;
        }

        $stringValue = $this->getValueAsString($value, $field, $element);

        return $stringValue !== '' ? [$stringValue] : [];
    }

    public function getValueForExport(mixed $value, CustomField $field, ?ElementInterface $element = null): mixed
    {
        return $this->getValueAsString($value, $field, $element);
    }

    public function getValueForIntegration(mixed $value, CustomField $field, IntegrationField $integrationField, IntegrationInterface $integration, ?ElementInterface $element = null, string $fieldKey = ''): mixed
    {
        $fieldValue = $integrationField->getType() === IntegrationField::TYPE_ARRAY
            ? $this->getValueAsArray($value, $field, $element)
            : $this->getValueAsString($value, $field, $element);

        return Integration::convertValueForIntegration($fieldValue, $integrationField);
    }

    public function getValueForSummary(mixed $value, CustomField $field, ?ElementInterface $element = null): mixed
    {
        return $this->getValueAsString($value, $field, $element);
    }

    public function getValueForReference(mixed $value, CustomField $field, ?ElementInterface $element = null): mixed
    {
        return $this->getValueAsString($value, $field, $element);
    }

    public function getValueForReferenceBlock(mixed $value, CustomField $field, Notification $notification, ?ElementInterface $element = null): mixed
    {
        return $value;
    }

    public function getValueForCondition(mixed $value, CustomField $field, Submission $submission): mixed
    {
        return $this->serializeValue($value, $field, $submission);
    }


    // Protected Methods
    // =========================================================================

    protected function getSvgIcon(): ?string
    {
        return null;
    }

    protected function settingName(string $name): string
    {
        return 'customFieldAdapterSettings.' . $name;
    }

    protected function getSetting(CustomField $field, string $name, mixed $default = null): mixed
    {
        return $field->getCustomFieldAdapterSetting($name, $default);
    }

    protected function getPlaceholder(CustomField $field): string
    {
        return trim((string)$this->getSetting($field, 'placeholder', ''));
    }

    protected function getCraftFieldIconSvg(): ?string
    {
        foreach (static::craftFieldClasses() as $fieldClass) {
            if (!class_exists($fieldClass) || !is_subclass_of($fieldClass, CraftField::class)) {
                continue;
            }

            $icon = $fieldClass::icon();

            if (!$icon) {
                continue;
            }

            return Cp::iconSvg($icon, $fieldClass::displayName());
        }

        return null;
    }

    protected function getSourceLabel(): ?string
    {
        return Craft::t('app', 'Craft CMS');
    }
}
