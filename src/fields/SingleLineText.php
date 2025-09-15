<?php
namespace verbb\formie\fields;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\fields\conditions\TextFieldConditionRule;
use verbb\formie\models\HtmlTag;

use Craft;
use craft\base\ElementInterface;
use craft\base\InlineEditableFieldInterface;
use craft\base\PreviewableFieldInterface;
use craft\base\SortableFieldInterface;
use craft\errors\InvalidFieldException;

use GraphQL\Type\Definition\Type;

class SingleLineText extends Field implements InlineEditableFieldInterface, PreviewableFieldInterface, SortableFieldInterface
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_UNIQUE_QUERY = 'modifyUniqueQuery';


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Single-line Text');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/single-line-text/icon.svg';
    }


    // Properties
    // =========================================================================

    public bool $limit = false;
    public ?int $min = null;
    public ?string $minType = 'characters';
    public ?int $max = null;
    public ?string $maxType = 'characters';
    public bool $uniqueValue = false;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        // Migrate legacy settings - remove at the next breakpoint
        if (array_key_exists('limitType', $config)) {
            $config['maxType'] = $config['limitType'];
            unset($config['limitType']);
        }
        
        // Migrate legacy settings - remove at the next breakpoint
        if (array_key_exists('limitAmount', $config)) {
            $config['max'] = $config['limitAmount'];
            unset($config['limitAmount']);
        }

        parent::__construct($config);
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        $value = $value !== '' ? $value : null;

        return parent::normalizeValue($value, $element);
    }

    public function getElementConditionRuleType(): ?string
    {
        return TextFieldConditionRule::class;
    }

    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();

        if ($this->limit) {
            if ($this->minType === 'characters') {
                $rules[] = [$this->handle, 'validateMinCharacters', 'skipOnEmpty' => false];
            }

            if ($this->maxType === 'characters') {
                $rules[] = 'validateMaxCharacters';
            }

            if ($this->minType === 'words') {
                $rules[] = [$this->handle, 'validateMinWords', 'skipOnEmpty' => false];
            }

            if ($this->maxType === 'words') {
                $rules[] = 'validateMaxWords';
            }
        }

        if ($this->uniqueValue) {
            $rules[] = 'validateUniqueValue';
        }

        return $rules;
    }

    public function validateMinCharacters(ElementInterface $element): void
    {
        $min = $this->min ?? 0;

        if (!$min) {
            return;
        }

        $value = (string)$element->getFieldValue($this->fieldKey);
        $count = StringHelper::getCharacterCount($value);

        if ($count < $min) {
            $element->addError($this->fieldKey, Craft::t('formie', 'You must enter at least {limit} characters.', [
                'limit' => $min,
            ]));
        }
    }

    public function validateMaxCharacters(ElementInterface $element): void
    {
        $max = $this->max ?? 0;

        if (!$max) {
            return;
        }

        $value = (string)$element->getFieldValue($this->fieldKey);
        $count = StringHelper::getCharacterCount($value);

        if ($count > $max) {
            $element->addError($this->fieldKey, Craft::t('formie', 'Limited to {limit} characters.', [
                'limit' => $max,
            ]));
        }
    }

    public function validateMinWords(ElementInterface $element): void
    {
        $min = $this->min ?? 0;

        if (!$min) {
            return;
        }

        $value = (string)$element->getFieldValue($this->fieldKey);
        $count = StringHelper::getWordCount($value);

        if ($count < $min) {
            $element->addError($this->fieldKey, Craft::t('formie', 'You must enter at least {limit} words.', [
                'limit' => $min,
            ]));
        }
    }

    public function validateMaxWords(ElementInterface $element): void
    {
        $max = $this->max ?? 0;

        if (!$max) {
            return;
        }

        $value = (string)$element->getFieldValue($this->fieldKey);
        $count = StringHelper::getWordCount($value);

        if ($count > $max) {
            $element->addError($this->fieldKey, Craft::t('formie', 'Limited to {limit} words.', [
                'limit' => $max,
            ]));
        }
    }

    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/single-line-text/preview', [
            'field' => $this,
        ]);
    }

    public function getFrontEndJsModules(): ?array
    {
        if ($this->limit) {
            return [
                'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/', true, 'js/fields/text-limit.js'),
                'module' => 'FormieTextLimit',
            ];
        }

        return null;
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'limit' => [
                'name' => 'limit',
                'type' => Type::boolean(),
            ],
            'min' => [
                'name' => 'min',
                'type' => Type::int(),
            ],
            'minType' => [
                'name' => 'minType',
                'type' => Type::string(),
            ],
            'max' => [
                'name' => 'max',
                'type' => Type::int(),
            ],
            'maxType' => [
                'name' => 'maxType',
                'type' => Type::string(),
            ],
            'uniqueValue' => [
                'name' => 'uniqueValue',
                'type' => Type::boolean(),
            ],
        ]);
    }

    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Placeholder'),
                'help' => Craft::t('formie', 'The text that will be shown if the field doesn’t have a value.'),
                'name' => 'placeholder',
            ]),
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'Default Value'),
                'help' => Craft::t('formie', 'Set a default value for the field when it doesn’t have a value.'),
                'name' => 'defaultValue',
                'variables' => 'userVariables',
            ]),
        ];
    }

    public function defineSettingsSchema(): array
    {
        return [
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Required Field'),
                'help' => Craft::t('formie', 'Whether this field should be required when filling out the form.'),
                'name' => 'required',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Error Message'),
                'help' => Craft::t('formie', 'When validating the form, show this message if an error occurs. Leave empty to retain the default message.'),
                'name' => 'errorMessage',
                'if' => '$get(required).value',
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Limit Value'),
                'help' => Craft::t('formie', 'Whether to limit the value of this field.'),
                'name' => 'limit',
            ]),
            [
                '$el' => 'div',
                'attrs' => [
                    'class' => 'fui-row',
                ],
                'if' => '$get(limit).value',
                'children' => [
                    [
                        '$el' => 'div',
                        'attrs' => [
                            'class' => 'fui-col-6',
                        ],
                        'children' => [
                            [
                                '$formkit' => 'fieldWrap',
                                'label' => Craft::t('formie', 'Min Value'),
                                'help' => Craft::t('formie', 'Set a minimum value that users must enter.'),
                                'children' => [
                                    [
                                        '$el' => 'div',
                                        'attrs' => [
                                            'class' => 'flex',
                                        ],
                                        'children' => [
                                            SchemaHelper::numberField([
                                                'name' => 'min',
                                                'inputClass' => 'text flex-grow',
                                            ]),
                                            SchemaHelper::selectField([
                                                'name' => 'minType',
                                                'options' => [
                                                    ['label' => Craft::t('formie', 'Characters'), 'value' => 'characters'],
                                                    ['label' => Craft::t('formie', 'Words'), 'value' => 'words'],
                                                ],
                                            ]),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        '$el' => 'div',
                        'attrs' => [
                            'class' => 'fui-col-6',
                        ],
                        'children' => [
                            [
                                '$formkit' => 'fieldWrap',
                                'label' => Craft::t('formie', 'Max Value'),
                                'help' => Craft::t('formie', 'Set a maximum value that users must enter.'),
                                'children' => [
                                    [
                                        '$el' => 'div',
                                        'attrs' => [
                                            'class' => 'flex',
                                        ],
                                        'children' => [
                                            SchemaHelper::numberField([
                                                'name' => 'max',
                                                'inputClass' => 'text flex-grow',
                                            ]),
                                            SchemaHelper::selectField([
                                                'name' => 'maxType',
                                                'options' => [
                                                    ['label' => Craft::t('formie', 'Characters'), 'value' => 'characters'],
                                                    ['label' => Craft::t('formie', 'Words'), 'value' => 'words'],
                                                ],
                                            ]),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            SchemaHelper::matchField([
                'fieldTypes' => [self::class],
            ]),
            SchemaHelper::prePopulate(),
            SchemaHelper::includeInEmailField(),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Unique Value'),
                'help' => Craft::t('formie', 'Whether to limit user input to unique values only. This will require that a value entered in this field does not already exist in a submission for this field and form.'),
                'name' => 'uniqueValue',
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
            SchemaHelper::inputAttributesField(),
            SchemaHelper::enableContentEncryptionField(),
        ];
    }

    public function defineConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }

    public function defineHtmlTag(string $key, array $context = []): ?HtmlTag
    {
        $form = $context['form'] ?? null;
        $errors = $context['errors'] ?? null;

        $id = $this->getHtmlId($form);
        $dataId = $this->getHtmlDataId($form);

        if ($key === 'fieldInput') {
            return new HtmlTag('input', [
                'type' => 'text',
                'id' => $id,
                'class' => [
                    'fui-input',
                    $errors ? 'fui-error' : false,
                ],
                'name' => $this->getHtmlName(),
                'placeholder' => Craft::t('formie', $this->placeholder) ?: null,
                'required' => $this->required ? true : null,
                'data' => [
                    'fui-id' => $dataId,
                    'required-message' => Craft::t('formie', $this->errorMessage) ?: null,
                    'min-chars' => ($this->limit && $this->minType === 'characters' && $this->min) ? $this->min : null,
                    'max-chars' => ($this->limit && $this->maxType === 'characters' && $this->max) ? $this->max : null,
                    'min-words' => ($this->limit && $this->minType === 'words' && $this->min) ? $this->min : null,
                    'max-words' => ($this->limit && $this->maxType === 'words' && $this->max) ? $this->max : null,
                ],
                'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
            ], $this->getInputAttributes());
        }

        if ($key === 'fieldLimit') {
            return new HtmlTag('div', [
                'class' => 'fui-limit-text',
                'data-limit' => true,
            ]);
        }

        return parent::defineHtmlTag($key, $context);
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['min', 'max'], 'number', 'integerOnly' => true];
        $rules[] = [['minType', 'maxType'], 'in', 'range' => ['characters', 'words']];

        return $rules;
    }

    protected function cpInputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/single-line-text/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
        ]);
    }
}
