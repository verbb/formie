<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormField;
use verbb\formie\base\MultiNestedFieldInterface;
use verbb\formie\base\NestedField;
use verbb\formie\gql\interfaces\RowInterface;
use verbb\formie\gql\types\input\RepeaterInputType;
use verbb\formie\gql\types\RowType;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\HtmlTag;

use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\validators\ArrayValidator;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

use Throwable;

class Repeater extends NestedField implements MultiNestedFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Repeater');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/repeater/icon.svg';
    }


    // Properties
    // =========================================================================

    public ?int $minRows = null;
    public ?int $maxRows = null;
    public ?string $addLabel = null;


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
        $value = $element->getFieldValue($this->handle);

        if ($element->getScenario() === Element::SCENARIO_LIVE && ($this->minRows || $this->maxRows)) {
            $arrayValidator = new ArrayValidator([
                'min' => $this->minRows ?: null,
                'max' => $this->maxRows ?: null,
                'tooFew' => $this->minRows ? Craft::t('app', '{attribute} should contain at least {min, number} {min, plural, one{block} other{blocks}}.', [
                    'attribute' => Craft::t('formie', $this->name),
                    'min' => $this->minRows, // Need to pass this in now
                ]) : null,
                'tooMany' => $this->maxRows ? Craft::t('app', '{attribute} should contain at most {max, number} {max, plural, one{block} other{blocks}}.', [
                    'attribute' => Craft::t('formie', $this->name),
                    'max' => $this->maxRows, // Need to pass this in now
                ]) : null,
                'skipOnEmpty' => false,
            ]);

            if (!$arrayValidator->validate($value, $error)) {
                $element->addError($this->handle, $error);
            }
        }

        foreach ($value as $rowKey => $row) {
            foreach ($this->getFields() as $field) {
                $fieldKey = "$this->handle.$rowKey.$field->handle";
                $subValue = $element->getFieldValue($fieldKey);
                $isEmpty = $field->isValueEmpty($subValue, $element);

                // Roll our own validation, due to lack of field layout and elements
                if ($field->required && $isEmpty) {
                    $element->addError($fieldKey, Craft::t('formie', '{attribute} cannot be blank.', ['attribute' => $field->name]));
                }

                foreach ($field->getElementValidationRules() as $rule) {
                    $attribute = $fieldKey;
                    $method = $rule[1];

                    if (!$isEmpty && $field->hasMethod($method)) {
                        $field->$method($element, $attribute);
                    }
                }
            }
        }
    }

    public function normalizeValue(mixed $value, ElementInterface $element = null): mixed
    {
        $fieldsByHandle = ArrayHelper::index($this->getFields(), 'handle');

        if (!is_array($value)) {
            $value = [];
        }

        // Normalize all inner fields
        foreach ($value as $rowKey => $row) {
            foreach ($row as $fieldHandle => $subValue) {
                $field = $fieldsByHandle[$fieldHandle] ?? null;

                if ($fieldHandle === $field?->handle) {
                    $value[$rowKey][$fieldHandle] = $field->normalizeValue($subValue, $element);
                }
            }
        }

        // Reset any `new1` or `row1` keys
        $value = array_values($value);

        return $value;
    }

    public function serializeValue(mixed $value, ElementInterface $element = null): mixed
    {
        $fieldsByHandle = ArrayHelper::index($this->getFields(), 'handle');

        if (!is_array($value)) {
            $value = [];
        }

        // Serialize all inner fields
        foreach ($value as $rowKey => $row) {
            foreach ($row as $fieldHandle => $subValue) {
                $field = $fieldsByHandle[$fieldHandle] ?? null;

                if ($fieldHandle === $field?->handle) {
                    $value[$rowKey][$fieldHandle] = $field->serializeValue($subValue, $element);
                }
            }
        }

        return $value;
    }

    public function getFieldTypeConfigDefaults(): array
    {
        return [
            'addLabel' => Craft::t('formie', 'Add another row'),
        ];
    }

    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        $view = Craft::$app->getView();

        $view->startJsBuffer();

        // Render it once to get the JS used for inner fields (element fields)
        $bodyHtml = $view->renderTemplate('formie/_formfields/repeater/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
        ]);;

        $footHtml = $view->clearJsBuffer();

        return $view->renderTemplate('formie/_formfields/repeater/input', [
            'element' => $element,
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
            'footHtml' => $footHtml,
        ]);
    }

    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/repeater/preview', [
            'field' => $this,
        ]);
    }

    public function getFrontEndJsModules(): ?array
    {
        $modules = parent::getFrontEndJsModules();

        $modules[] = [
            'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/js/fields/repeater.js', true),
            'module' => 'FormieRepeater',
        ];

        // Ensure we also load any JS in nested fields
        return $modules;
    }

    public function getConfigJson(): ?string
    {
        // Override `getConfigJson` as we don't want to initialise any inner fields immediately.
        // Even if there are min-rows, JS is the one to create the blocks, and initialise inner field JS.
        return Json::encode([
            'module' => 'FormieRepeater',
        ]);
    }

    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Add Label'),
                'help' => Craft::t('formie', 'The label for the button that adds another instance.'),
                'name' => 'addLabel',
                'validation' => 'required',
                'required' => true,
            ]),
        ];
    }

    public function defineSettingsSchema(): array
    {
        return [
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Minimum instances'),
                'help' => Craft::t('formie', 'The minimum required number of instances of this repeater‘s fields that must be completed.'),
                'name' => 'minRows',
            ]),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Maximum instances'),
                'help' => Craft::t('formie', 'The maximum required number of instances of this repeater‘s fields that must be completed.'),
                'name' => 'maxRows',
            ]),
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
        return RepeaterInputType::getType($this);
    }

    public function getContentGqlType(): array|Type
    {
        $typeName = ($this->getForm()->handle ?? '') . '_' . $this->handle . '_FormieRepeaterField';

        if ($inputType = GqlEntityRegistry::getEntity($typeName)) {
            return $inputType;
        }

        $rowTypeName = $typeName . 'Row';
        $repeaterFields = RowInterface::getFieldDefinitions();

        foreach ($this->getCustomFields() as $field) {
            $repeaterFields[$field->handle] = $field->getContentGqlType();
        }

        $rowType = GqlEntityRegistry::createEntity($rowTypeName, new RowType([
            'name' => $rowTypeName,
            'fields' => function() use ($repeaterFields) {
                return $repeaterFields;
            },
        ]));

        return GqlEntityRegistry::createEntity($typeName, new ObjectType([
            'name' => $typeName,
            'fields' => [
                'rows' => [
                    'name' => 'rows',
                    'type' => Type::listOf($rowType),
                    'resolve' => function($rootValue) {
                        return $rootValue;
                    },
                ],
            ],
        ]));
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
            return new HtmlTag('legend', [
                'class' => 'fui-legend',
            ]);
        }

        if ($key === 'nestedFieldContainer') {
            return new HtmlTag('div', [
                'class' => 'fui-repeater-rows',
                'data-repeater-rows' => true,
            ]);
        }

        if ($key === 'nestedField') {
            return new HtmlTag('div', [
                'class' => 'fui-repeater-row',
                'data-repeater-row' => true,
            ]);
        }

        if ($key === 'nestedFieldWrapper') {
            return new HtmlTag('fieldset', [
                'class' => 'fui-fieldset',
            ]);
        }

        if ($key === 'fieldAddButton') {
            $isStatic = false;

            // Disable the button straight away if we're making it static
            if ($this->minRows && $this->maxRows && $this->minRows == $this->maxRows) {
                $isStatic = true;
            }

            return new HtmlTag('button', [
                'class' => [
                    'fui-btn fui-repeater-add-btn',
                    $isStatic ? 'fui-disabled' : false,
                ],
                'type' => 'button',
                'text' => Craft::t('formie', $this->addLabel),
                'disabled' => $isStatic,
                'data' => [
                    'min-rows' => $this->minRows,
                    'max-rows' => $this->maxRows,
                    'add-repeater-row' => $this->handle,
                ],
            ]);
        }

        if ($key === 'fieldRemoveButton') {
            return new HtmlTag('button', [
                'class' => 'fui-btn fui-repeater-remove-btn',
                'type' => 'button',
                'text' => Craft::t('formie', 'Remove'),
                'data' => [
                    'remove-repeater-row' => $this->handle,
                ],
            ]);
        }

        return parent::defineHtmlTag($key, $context);
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['minRows', 'maxRows'], 'integer', 'min' => 0];
        
        return $rules;
    }


    protected function defineValueAsString($value, ElementInterface $element = null): string
    {
        $values = [];

        foreach ($value as $rowKey => $row) {
            foreach ($this->getFields() as $field) {
                $subValue = $element->getFieldValue("$this->handle.$rowKey.$field->handle");
                $valueAsString = $field->getValueAsString($subValue, $element);

                if ($valueAsString) {
                    $values[] = $valueAsString;
                }
            }
        }

        return implode(', ', $values);
    }

    protected function defineValueAsJson($value, ElementInterface $element = null): mixed
    {
        $values = [];

        foreach ($value as $rowKey => $row) {
            foreach ($this->getFields() as $field) {
                $subValue = $element->getFieldValue("$this->handle.$rowKey.$field->handle");
                $valueAsJson = $field->getValueAsJson($subValue, $element);

                if ($valueAsJson) {
                    $values[$rowKey][$field->handle] = $valueAsJson;
                }
            }
        }

        return $values;
    }

    protected function defineValueForExport($value, ElementInterface $element = null): mixed
    {
        $values = [];

        foreach ($value as $rowKey => $row) {
            foreach ($this->getFields() as $field) {
                $subValue = $element->getFieldValue("$this->handle.$rowKey.$field->handle");
                $valueForExport = $field->getValueForExport($subValue, $element);

                $key = $this->getExportLabel($element) . ': ' . ($rowKey + 1);

                if (is_array($valueForExport)) {
                    foreach ($valueForExport as $i => $j) {
                        $values[$key . ': ' . $i] = $j;
                    }
                } else {
                    $values[$key . ': ' . $field->getExportLabel($element)] = $valueForExport;
                }
            }
        }

        return $values;
    }

    protected function defineValueForSummary($value, ElementInterface $element = null): string
    {
        $values = '';

        foreach ($value as $rowKey => $row) {
            foreach ($this->getFields() as $field) {
                if ($field->getIsCosmetic() || $field->getIsHidden() || $field->isConditionallyHidden($element)) {
                    continue;
                }

                $subValue = $element->getFieldValue("$this->handle.$rowKey.$field->handle");
                $html = $field->getValueForSummary($subValue, $element);

                $values .= '<strong>' . $field->name . '</strong> ' . $html . '<br>';
            }
        }

        return Template::raw($values);
    }
}
