<?php
namespace verbb\formie\fields;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\base\RepeatableParentFieldInterface;
use verbb\formie\base\RepeatableParentField;
use verbb\formie\elements\Submission;
use verbb\formie\fields\definitions\FieldClientModules;
use verbb\formie\fields\definitions\FieldReferences;
use verbb\formie\fields\values\RepeaterFieldValue;
use verbb\formie\gql\interfaces\RowInterface;
use verbb\formie\gql\types\input\RepeaterInputType;
use verbb\formie\gql\types\RowType;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\ClientModule;
use verbb\formie\models\DynamicModel;
use verbb\formie\models\SlotTag;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Notification;
use verbb\formie\positions\Hidden as HiddenPosition;
use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\errors\GqlException;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Template;
use craft\validators\ArrayValidator;

use Faker\Generator as FakerFactory;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

use Throwable;

class Repeater extends RepeatableParentField
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

    public static function supportsGqlConfigProvider(): bool
    {
        return true;
    }

    public static function gqlContentMutationArgumentTypeFromConfig(array $config): Type|array
    {
        return RepeaterInputType::getTypeFromConfig($config);
    }

    public static function gqlContentTypeFromConfig(array $config): array|Type
    {
        $fieldsService = Formie::$plugin->getFields();
        $typeName = $fieldsService->getFieldConfigGqlTypeName($config, 'RepeaterField');

        if ($inputType = GqlEntityRegistry::getEntity($typeName)) {
            return $inputType;
        }

        $rowTypeName = $fieldsService->getFieldConfigGqlTypeName($config, 'RepeaterRow');
        $repeaterFields = RowInterface::getFieldDefinitions();
        $schema = null;

        try {
            $schema = Craft::$app->getGql()->getActiveSchema();
        } catch (GqlException $e) {
            Craft::warning("Could not get the active GraphQL schema: {$e->getMessage()}", __METHOD__);
            Craft::$app->getErrorHandler()->logException($e);
        }

        foreach ($fieldsService->getNestedFieldConfigs($config) as $fieldConfig) {
            $handle = $fieldConfig['handle'] ?? null;

            if (!$handle) {
                continue;
            }

            if (!$schema || $fieldsService->fieldConfigIncludedInGqlSchema($fieldConfig, $schema)) {
                $repeaterFields[$handle] = $fieldsService->getFieldConfigContentGqlType($fieldConfig);
            }
        }

        $rowType = GqlEntityRegistry::createEntity($rowTypeName, new RowType([
            'name' => $rowTypeName,
            'fields' => function() use ($repeaterFields, $rowTypeName) {
                return Craft::$app->getGql()->prepareFieldDefinitions($repeaterFields, $rowTypeName);
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

                        if (is_array($rootValue)) {
                            foreach ($rootValue as $rowValue) {
                                $values[] = new DynamicModel($rowValue);
                            }
                        }

                        return $values;
                    },
                ],
            ],
        ]));
    }
    

    // Properties
    // =========================================================================

    public ?int $minRows = null;
    public ?int $maxRows = null;
    public ?string $addLabel = null;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        // Setuo defaults for some values which can't in in the property definition
        $config['addLabel'] = $config['addLabel'] ?? Craft::t('formie', 'Add another row');

        parent::__construct($config);
    }

    public function fieldKind(): string
    {
        return self::KIND_REPEATER;
    }

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewRepeater(),
        ];
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

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Add Label'),
                'instructions' => Craft::t('formie', 'The label for the button that adds another instance.'),
                'name' => 'addLabel',
                'validation' => 'required',
                'required' => true,
            ]),
        ];
    }

    public function defineFormBuilderSettingsSchema(): array
    {
        return [
            SchemaHelper::includeInEmailFieldSummariesField(),
        ];
    }

    public function defineFormBuilderValidationSchema(): array
    {
        return [
            SchemaHelper::minRowsField(),
            SchemaHelper::maxRowsField(),
        ];
    }

    public function defineFormBuilderAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
            SchemaHelper::errorMessagePosition($this),
        ];
    }

    public function defineFormBuilderAdvancedSchema(): array
    {
        return [
            SchemaHelper::handleField(),
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
        ];
    }

    public function defineFormBuilderConditionsSchema(): array
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
        $fieldsService = Formie::$plugin->getFields();

        foreach ($this->getFields() as $field) {
            $repeaterFields[$field->handle] = $fieldsService->getFieldContentGqlType($field);
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

    // Protected Methods
    // =========================================================================

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $form = $context->form;
        $id = $this->getHtmlId($form);
        $templateId = "{$id}-template";
        $labelPosition = $context->get('labelPosition');

        if ($key === 'fieldLayout') {
            return SlotTag::make('fieldset')
                ->core([
                    'data-formie-field-layout' => true,
                    'data-formie-repeater-field-layout' => true,
                    'data-formie-template-id' => $templateId,
                    'aria-describedby' => $this->hasInstructions() ? "{$id}-instructions" : null,
                ])
                ->theme([
                    'class' => [
                        'formie-field-layout',
                        'formie-repeater-field-layout',
                    ],
                ]);
        }

        if ($key === 'fieldLabel') {
            return SlotTag::make('legend')
                ->core([
                    'data-formie-label' => true,
                    'data-formie-field-label' => true,
                    'data-formie-repeater-field-label' => true,
                    'data-formie-sr-only' => $labelPosition instanceof HiddenPosition ? true : false,
                ])
                ->theme([
                    'class' => [
                        'formie-label',
                        'formie-field-label',
                        'formie-repeater-field-label',
                        $labelPosition instanceof HiddenPosition ? 'formie-sr-only' : false,
                    ],
                ]);
        }

        if ($key === 'nestedFieldContainer') {
            return SlotTag::make('div')
                ->core([
                    'data-formie-nested-field-container' => true,
                    'data-formie-repeater-container' => true,
                    'data-formie-template-id' => $templateId,
                ])
                ->theme([
                    'class' => [
                        'formie-nested-field-container',
                        'formie-repeater-container',
                    ],
                ]);
        }

        if ($key === 'nestedField') {
            return SlotTag::make('div')
                ->core([
                    'data-formie-nested-field' => true,
                    'data-formie-repeater-item' => '__ROW__',
                    'data-formie-repeater-item-id' => '__ROW__',
                ])
                ->theme([
                    'class' => [
                        'formie-nested-field',
                        'formie-repeater-item',
                    ],
                ]);
        }

        if ($key === 'nestedFieldWrapper') {
            return SlotTag::make('fieldset')
                ->core([
                    'data-formie-repeater-item-wrapper' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-repeater-item-wrapper',
                    ],
                ]);
        }

        if ($key === 'fieldAddButton') {
            $isStatic = false;

            // Disable the button straight away if we're making it static
            if ($this->minRows && $this->maxRows && $this->minRows == $this->maxRows) {
                $isStatic = true;
            }

            return SlotTag::make('button')
                ->core([
                    'type' => 'button',
                    'text' => Craft::t('formie', $this->addLabel),
                    'disabled' => $isStatic,
                    'data-formie-add-button' => true,
                    'data-formie-repeater-add' => $this->handle,
                    'data-formie-template-id' => $templateId,
                    'data-formie-min-rows' => $this->minRows,
                    'data-formie-max-rows' => $this->maxRows,
                ])
                ->theme([
                    'class' => [
                        'formie-button',
                        'formie-repeater-add-button',
                    ],
                ]);
        }

        if ($key === 'fieldRemoveButton') {
            return SlotTag::make('button')
                ->core([
                    'type' => 'button',
                    'text' => Craft::t('formie', 'Remove'),
                    'aria-label' => Craft::t('formie', 'Remove row'),
                    'title' => Craft::t('formie', 'Remove row'),
                    'data-formie-remove-button' => true,
                    'data-formie-icon' => 'close',
                    'data-formie-repeater-remove' => $this->handle,
                ])
                ->theme([
                    'class' => [
                        'formie-button',
                        'formie-button-icon',
                        'formie-repeater-remove-button',
                    ],
                ]);
        }

        return parent::defineFieldSlotTag($key, $context);
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['minRows', 'maxRows'], 'integer', 'min' => 0];
        
        return $rules;
    }

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
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

    protected function getNestedLayoutBuilderDisallowedFieldTypes(): array
    {
        return [
            self::class,
            Group::class,
        ];
    }

    protected function defineAllowPrimaryReference(): bool
    {
        return false;
    }

    protected function defineAllowNestedReference(): bool
    {
        return true;
    }

    protected function defineNestedReferenceMode(): string
    {
        return 'childrenOnly';
    }

    protected function defineClientInput(): array
    {
        return array_merge(parent::defineClientInput(), [
            'minRows' => $this->minRows,
            'maxRows' => $this->maxRows,
            'addLabel' => $this->addLabel,
        ]);
    }

    protected function defineClientModules(): array
    {
        $modules = parent::defineClientModules();
        $modules[] = new ClientModule([
            'id' => 'repeater',
        ]);

        return $modules;
    }

    protected function defineValueClass(): ?string
    {
        return RepeaterFieldValue::class;
    }
}
