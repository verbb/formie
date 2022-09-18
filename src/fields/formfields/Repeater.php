<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormField;
use verbb\formie\base\NestedFieldInterface;
use verbb\formie\base\NestedFieldTrait;
use verbb\formie\elements\db\NestedFieldRowQuery;
use verbb\formie\elements\NestedFieldRow;
use verbb\formie\gql\interfaces\RowInterface;
use verbb\formie\gql\types\input\RepeaterInputType;
use verbb\formie\gql\types\RowType;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\HtmlTag;

use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Json;
use craft\validators\ArrayValidator;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

use Throwable;

class Repeater extends FormField implements NestedFieldInterface, EagerLoadingFieldInterface
{
    // Traits
    // =========================================================================

    use NestedFieldTrait {
        validateRows as traitValidateRows;
        getFrontEndJsModules as traitGetFrontEndJsModules;
    }


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Repeater');
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/repeater/icon.svg';
    }

    /**
     * @inheritdoc
     */
    public static function hasContentColumn(): bool
    {
        return false;
    }


    // Properties
    // =========================================================================

    public ?int $minRows = null;
    public ?int $maxRows = null;
    public ?string $addLabel = null;


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();

        $rules[] = [
            'validateRows',
            'on' => [Element::SCENARIO_ESSENTIALS, Element::SCENARIO_DEFAULT, Element::SCENARIO_LIVE],
            'skipOnEmpty' => false,
        ];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function validateRows(ElementInterface $element): void
    {
        $this->traitValidateRows($element);

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

            $value = $element->getFieldValue($this->handle);
            $blocks = $value->all();

            if (!$arrayValidator->validate($blocks, $error)) {
                $element->addError($this->handle, $error);
            }
        }
    }

    /**
     * @return array
     */
    public function getFieldDefaults(): array
    {
        return [
            'addLabel' => Craft::t('formie', 'Add another row'),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
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
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
            'footHtml' => $footHtml,
        ]);;
    }

    /**
     * @inheritDoc
     */
    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/repeater/preview', [
            'field' => $this,
        ]);
    }

    public function populateValue($value): void
    {
        if (!is_array($value) || !isset($value[0])) {
            return;
        }

        $blocks = [];

        foreach ($value as $i => $fieldContent) {
            try {
                $row = new NestedFieldRow();
                $row->id = 'new' . ($i + 1);
                $row->fieldId = $this->id;
                $row->setFieldValues($fieldContent);

                $blocks[] = $row;
            } catch (Throwable $e) {
                continue;
            }
        }

        if ($blocks) {
            $this->defaultValue = new NestedFieldRowQuery(NestedFieldRow::class);
            $this->defaultValue->setBlocks($blocks);
        }
    }

    public function parsePopulatedFieldValues($value, $element): array
    {
        // For when parsing populated content from the cache, when the field is visibly disabled
        // It's supplied in a format that makes sense for `populateValue()` but not for `$element->setFieldValue()`.
        $rows = [];

        foreach ($value as $i => $fields) {
            $rows['new' . ($i + 1)]['fields'] = $fields;
        }

        return $rows;
    }

    public function getFrontEndJsModules(): ?array
    {
        $modules = [$this->traitGetFrontEndJsModules()];

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

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
        ];
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    public function getContentGqlMutationArgumentType(): array|Type
    {
        return RepeaterInputType::getType($this);
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['minRows', 'maxRows'], 'integer', 'min' => 0];
        return $rules;
    }
}
