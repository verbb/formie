<?php
namespace verbb\formie\fields;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\base\MultiNestedFieldInterface;
use verbb\formie\base\MultiNestedField;
use verbb\formie\elements\Submission;
use verbb\formie\gql\interfaces\RowInterface;
use verbb\formie\gql\types\input\RepeaterInputType;
use verbb\formie\gql\types\RowType;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\DynamicModel;
use verbb\formie\models\HtmlTag;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Notification;
use verbb\formie\positions\Hidden as HiddenPosition;

use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\validators\ArrayValidator;

use Faker\Generator as FakerFactory;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

use Throwable;

class Repeater extends MultiNestedField
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

    public function getFieldTypeDefaults(): array
    {
        // Setup defaults for some values which can't be set in the property definition
        $settings = parent::getFieldTypeDefaults();
        $settings['addLabel'] = Craft::t('formie', 'Add another row');

        return $settings;
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
            'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/', true, 'js/fields/repeater.js'),
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

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'minRows' => [
                'name' => 'minRows',
                'type' => Type::int(),
            ],
            'maxRows' => [
                'name' => 'maxRows',
                'type' => Type::int(),
            ],
            'addLabel' => [
                'name' => 'addLabel',
                'type' => Type::string(),
            ],
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
            SchemaHelper::includeInEmailField(),
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

    public function getContentGqlMutationArgumentType(): Type|array
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

        foreach ($this->getFields() as $field) {
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
                        $values = [];

                        // Some fields like the in-built elements (Assets, Entries, etc) will assume the value of a repeater row
                        // in an element, but it's not. Instead make it a dynamic model that'll work for the most part.
                        if (is_array($rootValue)) {
                            foreach ($rootValue as $value) {
                                $values[] = new DynamicModel($value);
                            }
                        }

                        return $values;
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
            $labelPosition = $context['labelPosition'] ?? null;

            return new HtmlTag('legend', [
                'class' => [
                    'fui-legend',
                ],
                'data' => [
                    'field-label' => true,
                    'fui-sr-only' => $labelPosition instanceof HiddenPosition ? true : false,
                ],
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
                'data-repeater-row' => '__ROW__',
                'data-repeater-row-id' => '__ROW__',
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

    protected function cpInputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
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

    protected function defineValueForEmailPreview(FakerFactory $faker): mixed
    {
        return [
            Formie::$plugin->getSubmissions()->getFakeFieldContent($this->getFields()),
            Formie::$plugin->getSubmissions()->getFakeFieldContent($this->getFields()),
        ];
    }
}
