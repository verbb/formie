<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormField;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\base\NestedField;
use verbb\formie\base\SingleNestedFieldInterface;
use verbb\formie\gql\resolvers\elements\NestedFieldRowResolver;
use verbb\formie\gql\types\generators\NestedFieldGenerator;
use verbb\formie\gql\types\input\GroupInputType;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\HtmlTag;
use verbb\formie\models\IntegrationField;
use verbb\formie\positions\Hidden as HiddenPosition;

use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Gql;
use craft\helpers\Template;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

use yii\validators\Validator;

class Group extends NestedField implements SingleNestedFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Group');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/group/icon.svg';
    }

    public static function gqlTypeNameByContext(mixed $context): string
    {
        return ($context->getForm()->handle ?? '') . '_' . $context->handle . '_FormieGroupField';
    }


    // Public Methods
    // =========================================================================

    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();

        $rules[] = [
            'validateBlocks',
            'on' => [Element::SCENARIO_ESSENTIALS, Element::SCENARIO_DEFAULT, Element::SCENARIO_LIVE],
            'skipOnEmpty' => false,
        ];

        return $rules;
    }

    public function validateBlocks(ElementInterface $element): void
    {
        foreach ($this->getFields() as $field) {
            $fieldKey = "$this->handle.$field->handle";
            $value = $element->getFieldValue($fieldKey);
            $isEmpty = $field->isValueEmpty($value, $element);

            // Ensure that the inner fields know about this the parent field, to handle getting values properly
            $field->setParentField($this);

            // Roll our own validation, due to lack of field layout and elements
            if ($field->required && $isEmpty) {
                $element->addError($fieldKey, Craft::t('formie', '{attribute} cannot be blank.', ['attribute' => $field->name]));
            }

            foreach ($field->getElementValidationRules() as $rule) {
                $this->normalizeFieldValidator($fieldKey, $rule, $field, $element, $isEmpty);
            }
        }
    }

    public function normalizeValue(mixed $value, ElementInterface $element = null): mixed
    {
        if (!is_array($value)) {
            $value = [];
        }

        // Normalize all inner fields
        foreach ($this->getFields() as $field) {
            $fieldValue = $value[$field->handle] ?? null;

            $value[$field->handle] = $field->normalizeValue($fieldValue, $element);
        }

        return $value;
    }

    public function serializeValue(mixed $value, ElementInterface $element = null): mixed
    {
        if (!is_array($value)) {
            $value = [];
        }

        // Serialize all inner fields
        foreach ($this->getFields() as $field) {
            $fieldValue = $value[$field->handle] ?? null;

            $value[$field->handle] = $field->serializeValue($fieldValue, $element);
        }

        return $value;
    }

    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/group/preview', [
            'field' => $this,
        ]);
    }

    public function getConfigJson(): ?string
    {
        // Group fields themselves should not contain the inner field's JS
        return null;
    }

    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
        ];
    }

    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
        ];
    }

    public function defineAdvancedSchema(): array
    {
        return [
            SchemaHelper::handleField(),
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
        ];
    }

    public function defineConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }

    public function getContentGqlMutationArgumentType(): array|Type
    {
        return GroupInputType::getType($this);
    }

    public function getContentGqlType(): array|Type
    {
        $typeArray = NestedFieldGenerator::generateTypes($this);
        $typeName = self::gqlTypeNameByContext($this);

        return [
            'name' => $this->handle,
            'type' => Type::nonNull(Gql::getUnionType($typeName, $typeArray)),
            'resolve' => NestedFieldRowResolver::class . '::resolve',
            'complexity' => Gql::eagerLoadComplexity(),
        ];
    }

    public function defineHtmlTag(string $key, array $context = []): ?HtmlTag
    {
        $form = $context['form'] ?? null;

        $id = $this->getHtmlId($form);

        if ($key === 'fieldContainer') {
            return new HtmlTag('fieldset', [
                'class' => 'fui-fieldset',
                'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
            ]);
        }

        if ($key === 'fieldLabel') {
            $labelPosition = $context['labelPosition'] ?? null;

            return new HtmlTag('legend', [
                'class' => [
                    'fui-legend',
                ],
                'data' => [
                    'fui-sr-only' => $labelPosition instanceof HiddenPosition ? true : false,
                ],
            ]);
        }

        if ($key === 'nestedFieldContainer') {
            return new HtmlTag('div', [
                'class' => 'fui-group',
            ]);
        }

        return parent::defineHtmlTag($key, $context);
    }


    // Protected Methods
    // =========================================================================

    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/group/input', [
            'element' => $element,
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
        ]);
    }

    protected function defineValueAsString(mixed $value, ElementInterface $element = null): string
    {
        $values = [];

        foreach ($this->getFields() as $field) {
            $subValue = $element->getFieldValue("$this->handle.$field->handle");
            $valueAsString = $field->getValueAsString($subValue, $element);

            if ($valueAsString) {
                $values[] = $valueAsString;
            }
        }

        return implode(', ', $values);
    }

    protected function defineValueAsJson(mixed $value, ElementInterface $element = null): mixed
    {
        $values = [];

        foreach ($this->getFields() as $field) {
            $subValue = $element->getFieldValue("$this->handle.$field->handle");
            $valueAsJson = $field->getValueAsJson($subValue, $element);

            if ($valueAsJson) {
                $values[$field->handle] = $valueAsJson;
            }
        }

        return $values;
    }

    protected function defineValueForExport(mixed $value, ElementInterface $element = null): mixed
    {
        $values = [];

        foreach ($this->getFields() as $field) {
            $subValue = $element->getFieldValue("$this->handle.$field->handle");
            $valueForExport = $field->getValueForExport($subValue, $element);

            $key = $this->getExportLabel($element);

            if (is_array($valueForExport)) {
                foreach ($valueForExport as $i => $j) {
                    $values[$key . ': ' . $i] = $j;
                }
            } else {
                $values[$key . ': ' . $field->getExportLabel($element)] = $valueForExport;
            }
        }

        return $values;
    }

    protected function defineValueForSummary(mixed $value, ElementInterface $element = null): string
    {
        $values = '';

        foreach ($this->getFields() as $field) {
            if ($field->getIsCosmetic() || $field->getIsHidden() || $field->isConditionallyHidden($element)) {
                continue;
            }

            $subValue = $element->getFieldValue("$this->handle.$field->handle");
            $html = $field->getValueForSummary($subValue, $element);

            $values .= '<strong>' . $field->name . '</strong> ' . $html . '<br>';
        }

        return Template::raw($values);
    }

    protected function defineValueForIntegration(mixed $value, IntegrationField $integrationField, IntegrationInterface $integration, ElementInterface $element = null, string $fieldKey = ''): mixed
    {
        // Check if we're trying to get a sub-field value
        if ($fieldKey) {
            $subFieldKey = explode('.', $fieldKey);
            $subFieldHandle = array_shift($subFieldKey);
            $subFieldKey = implode('.', $subFieldKey);

            $subField = $this->getFieldByHandle($subFieldHandle);
            $subValue = $element->getFieldValue("$this->handle.$subFieldHandle");

            return $subField->getValueForIntegration($subValue, $integrationField, $integration, $element, $subFieldKey);
        }

        // Fetch the default handling
        return parent::defineValueForIntegration($value, $integrationField, $integration, $element, $fieldKey);
    }
}
