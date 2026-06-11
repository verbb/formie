<?php
namespace verbb\formie\fields;

use verbb\formie\base\Field;
use verbb\formie\base\PreviewableFieldInterface;
use verbb\formie\base\RepeatableParentFieldInterface;
use verbb\formie\fields\definitions\FieldClientModules;
use verbb\formie\fields\definitions\FieldReferenceValue;
use verbb\formie\fields\values\CalculationFieldValue;
use verbb\formie\gql\types\generators\FieldAttributeGenerator;
use verbb\formie\helpers\FieldReferenceHelper;
use verbb\formie\helpers\References;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\ValidationMessagesHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\ClientModule;
use verbb\formie\models\SlotTag;
use verbb\formie\models\RichText;

use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Json;

use GraphQL\Type\Definition\Type;

class Calculations extends Field implements PreviewableFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Calculations');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/calculations/icon.svg';
    }


    // Properties
    // =========================================================================

    public RichText $formula;
    public string $formatting = '';
    public string $prefix = '';
    public string $suffix = '';
    public int $decimals = 0;

    private ?array $_renderedFormula = null;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        $config['formula'] = RichText::from($config['formula'] ?? null);

        parent::__construct($config);
    }

    public function fieldKind(): string
    {
        return self::KIND_TEXT;
    }

    public function setAttributes($values, $safeOnly = true): void
    {
        if (is_array($values)) {
            $hasFormula = array_key_exists('formula', $values);

            if ($hasFormula) {
                $values['formula'] = RichText::from($values['formula']);
            }

            if ($hasFormula) {
                $this->_renderedFormula = null;
            }
        }

        parent::setAttributes($values, $safeOnly);
    }

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewInput(),
        ];
    }

    public function getFormula(): array
    {
        if ($this->_renderedFormula) {
            return $this->_renderedFormula;
        }

        $formula = trim($this->formula->toPlainText());
        $variables = [];
        $form = $this->getForm();
        $fieldMap = $form ? FieldReferenceHelper::getClientFieldReferenceMap($form->getFields()) : [];
        $variableIdsBySourceKey = [];
        $sourceKeyByVariableId = [];

        $formula = preg_replace_callback('/\{field:[^}]+\}/', function($matches) use (&$variables, &$variableIdsBySourceKey, &$sourceKeyByVariableId, $fieldMap, $form) {
            $token = (string)($matches[0] ?? '');
            $expression = References::parseReferenceExpression($token);
            
            if (!$expression->isValid || $expression->target !== 'field' || $expression->identifier === '') {
                return '';
            }

            $sourceHandle = FieldReferenceHelper::resolveClientFieldKey($expression->identifier, $fieldMap);
            $sourceHandle = trim($sourceHandle);

            if ($expression->selector !== '') {
                $sourceHandle = $sourceHandle !== ''
                    ? "{$sourceHandle}.{$expression->selector}"
                    : $expression->identifier . ".{$expression->selector}";
            }

            if ($sourceHandle === '') {
                $sourceHandle = $expression->identifier;
            }

            $scopeParams = array_intersect_key(
                $expression->transformerParams,
                array_flip(['scope', 'index', 'rows']),
            );

            $referencedField = null;

            if ($form) {
                foreach ($form->getFields() as $field) {
                    if ((string)($field->reference ?? '') === $expression->identifier) {
                        $referencedField = $field;
                        break;
                    }
                }
            }

            $variableConfig = [
                'sourceKey' => $sourceHandle,
            ];

            if ($referencedField instanceof Table) {
                $variableConfig['fieldKind'] = Table::KIND_TABLE;
            } elseif ($referencedField instanceof RepeatableParentFieldInterface) {
                $variableConfig['fieldKind'] = 'repeater';
            }

            foreach ($scopeParams as $paramKey => $paramValue) {
                if ($paramValue === null || $paramValue === '') {
                    continue;
                }

                $variableConfig[$paramKey] = (string)$paramValue;
            }

            $variableDedupKey = Json::encode($variableConfig);

            if (isset($variableIdsBySourceKey[$variableDedupKey])) {
                return $variableIdsBySourceKey[$variableDedupKey];
            }

            $baseVariableId = 'field_' . preg_replace('/[^A-Za-z0-9_]/', '_', $sourceHandle);
            $baseVariableId = trim((string)$baseVariableId, '_');

            if ($baseVariableId === '' || preg_match('/^[0-9]/', $baseVariableId)) {
                $baseVariableId = 'field_var';
            }

            if ($scopeParams !== []) {
                $scopeSuffix = preg_replace('/[^A-Za-z0-9_]/', '_', implode('_', array_values($scopeParams)));
                $scopeSuffix = trim((string)$scopeSuffix, '_');

                if ($scopeSuffix !== '') {
                    $baseVariableId .= '_' . $scopeSuffix;
                }
            }

            $variableId = $baseVariableId;
            $suffix = 2;

            while (isset($sourceKeyByVariableId[$variableId]) && $sourceKeyByVariableId[$variableId] !== $variableDedupKey) {
                $variableId = "{$baseVariableId}_{$suffix}";
                $suffix++;
            }

            $variableIdsBySourceKey[$variableDedupKey] = $variableId;
            $sourceKeyByVariableId[$variableId] = $variableDedupKey;
            $variables[$variableId] = $variableConfig;

            return $variableId;
        }, $formula) ?? $formula;

        return $this->_renderedFormula = [
            'expression' => $formula,
            'formula' => $formula,
            'variables' => $variables,
        ];
    }

    public function getSettings(): array
    {
        $settings = parent::getSettings();
        $settings['formula'] = $this->formula->getSchema();

        return $settings;
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'formula' => [
                'name' => 'formula',
                'type' => Type::string(),
                'resolve' => function($field) {
                    return Json::encode($field->getFormula());
                },
            ],
            'formatting' => [
                'name' => 'formatting',
                'type' => Type::string(),
            ],
            'prefix' => [
                'name' => 'prefix',
                'type' => Type::string(),
            ],
            'suffix' => [
                'name' => 'suffix',
                'type' => Type::string(),
            ],
            'decimals' => [
                'name' => 'decimals',
                'type' => Type::int(),
            ],
        ]);
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::calculationsField([
                'label' => Craft::t('formie', 'Calculations Formula'),
                'instructions' => Craft::t('formie', 'Provide the formula used to calculate the result for this field. Use arithmetic operators (`+`, `-`, `*`, `/`, etc) and reference other fields.'),
                'name' => 'formula',
                'validationAction' => 'formie/fields/validate-calculations-formula',
                'variableConfig' => [
                    'content' => Variables::CONTENT_SINGLE_LINE,
                    'types' => [Variables::TYPE_TEXT, Variables::TYPE_EMAIL, Variables::TYPE_NUMBER],
                    'groups' => [
                        Variables::STATIC_FIELDS,
                    ],
                    'referenceContext' => 'client',
                    'fieldSelectionPageScope' => 'currentAndPrevious',
                    'excludeSelf' => true,
                ],
            ]),
        ];
    }

    public function defineFormBuilderSettingsSchema(): array
    {
        return [
            SchemaHelper::includeInEmailFieldSummariesField(),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Formatting'),
                'instructions' => Craft::t('formie', 'Select how to format the value calculated for this field.'),
                'name' => 'formatting',
                'options' => [
                    ['label' => Craft::t('formie', 'None'), 'value' => ''],
                    ['label' => Craft::t('formie', 'Number'), 'value' => 'number'],
                ],
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Prefix'),
                'instructions' => Craft::t('formie', 'Add a prefix to the number.'),
                'name' => 'prefix',
                'if' => 'formatting == "number"',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Suffix'),
                'instructions' => Craft::t('formie', 'Add a suffix to the number.'),
                'name' => 'suffix',
                'if' => 'formatting == "number"',
            ]),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Decimal Rounding'),
                'instructions' => Craft::t('formie', 'How many decimals to round the number to.'),
                'name' => 'decimals',
                'if' => 'formatting == "number"',
            ]),
        ];
    }

    public function defineFormBuilderValidationSchema(): array
    {
        return [
            SchemaHelper::requiredField(),
            SchemaHelper::requiredValidationMessage(),
            SchemaHelper::matchField([
                'includedTypes' => [self::class],
            ]),
            SchemaHelper::matchValidationMessage(),
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
            SchemaHelper::inputAttributesField(),
            SchemaHelper::enableContentEncryptionField(),
        ];
    }

    public function defineFormBuilderConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }

    // Protected Methods
    // =========================================================================

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $form = $context->form;

        $id = $this->getHtmlId($form);
        $dataId = $this->getHtmlDataId($form);

        if ($key === 'fieldInput') {
            return SlotTag::make('input')
                ->core(array_merge([
                    'type' => 'text',
                    'id' => $id,
                    'name' => $this->getHtmlName(),
                    'placeholder' => Craft::t('formie', $this->placeholder) ?: null,
                    'required' => $this->required ? true : null,
                    'readonly' => true,
                    'data-formie-input' => true,
                    'data-formie-calculation-input' => true,
                    'data-formie-input-id' => $dataId,
                    'data-formie-input-type' => 'calculation',
                    'aria-describedby' => $this->hasInstructions() ? "{$id}-instructions" : null,
                ], ValidationMessagesHelper::requiredClientAttributes($this)))
                ->theme([
                    'class' => [
                        'formie-input',
                        'formie-calculation-input',
                    ],
                ])
                ->instanceAttributes($this->getInputAttributes());
        }
        
        return parent::defineFieldSlotTag($key, $context);
    }

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/calculations/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
        ]);
    }

    protected function defineClientModules(): array
    {
        $modules = parent::defineClientModules();
        
        $modules[] = new ClientModule([
            'id' => 'calculations',
            'config' => [
                'formula' => $this->getFormula(),
                'formatting' => $this->formatting,
                'prefix' => $this->prefix,
                'suffix' => $this->suffix,
                'decimals' => $this->decimals,
            ],
        ]);

        return $modules;
    }

    protected function defineReferenceValues(): array
    {
        return [
            FieldReferenceValue::default([
                'variableTypes' => [
                    Variables::TYPE_CALCULATIONS,
                    Variables::TYPE_NUMBER,
                    Variables::TYPE_TEXT,
                ],
            ]),
        ];
    }

    protected function defineValueClass(): ?string
    {
        return CalculationFieldValue::class;
    }
}
