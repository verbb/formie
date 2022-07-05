<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\Formie;
use verbb\formie\base\FormField;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\HtmlTag;

use Craft;
use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;
use craft\errors\InvalidFieldException;
use craft\helpers\StringHelper;

class SingleLineText extends FormField implements PreviewableFieldInterface
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Single-line Text');
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/single-line-text/icon.svg';
    }

    // Properties
    // =========================================================================

    public bool $limit = false;
    public ?string $limitType = null;
    public ?int $limitAmount = null;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();

        if ($this->limit) {
            $limitType = $this->limitType ?? '';

            if ($limitType === 'characters') {
                $rules[] = 'validateMaxCharacters';
            }

            if ($limitType === 'words') {
                $rules[] = 'validateMaxWords';
            }
        }

        return $rules;
    }

    /**
     * Validates the maximum number of characters.
     *
     * @param ElementInterface $element
     * @throws InvalidFieldException
     */
    public function validateMaxCharacters(ElementInterface $element): void
    {
        $limitAmount = (int)($this->limitAmount ?? 0);

        if (!$limitAmount) {
            return;
        }

        $value = $element->getFieldValue($this->handle);
        $count = strlen($value);

        if ($count > $limitAmount) {
            $element->addError(
                $this->handle,
                Craft::t('formie', 'Limited to {limit} characters.', [
                    'limit' => $limitAmount,
                ])
            );
        }
    }

    /**
     * Validates the maximum number of words.
     *
     * @param ElementInterface $element
     * @throws InvalidFieldException
     */
    public function validateMaxWords(ElementInterface $element): void
    {
        $limitAmount = (int)($this->limitAmount ?? 0);

        if (!$limitAmount) {
            return;
        }

        $value = $element->getFieldValue($this->handle);
        $count = count(explode(' ', $value));

        if ($count > $limitAmount) {
            $element->addError(
                $this->handle,
                Craft::t('formie', 'Limited to {limit} words.', [
                    'limit' => $limitAmount,
                ])
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/single-line-text/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
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
                'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/js/fields/text-limit.js', true),
                'module' => 'FormieTextLimit',
            ];
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function getFieldDefaults(): array
    {
        return [
            'limitType' => 'characters',
        ];
    }

    /**
     * @inheritDoc
     */
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
                'help' => Craft::t('formie', 'Entering a default value will place the value in the field when it loads.'),
                'name' => 'defaultValue',
                'variables' => 'userVariables',
            ]),
        ];
    }

    /**
     * @inheritDoc
     */
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
                'label' => Craft::t('formie', 'Limit Field Content'),
                'help' => Craft::t('formie', 'Whether to limit the content of this field.'),
                'name' => 'limit',
            ]),
            [
                '$formkit' => 'fieldWrap',
                'label' => Craft::t('formie', 'Limit'),
                'help' => Craft::t('formie', 'Enter the number of characters or words to limit this field by.'),
                'if' => '$get(limit).value',
                'children' => [
                    [
                        '$el' => 'div',
                        'attrs' => [
                            'class' => 'flex',
                        ],
                        'children' => [
                            SchemaHelper::numberField([
                                'name' => 'limitAmount',
                                'inputClass' => 'text flex-grow',
                            ]),
                            SchemaHelper::selectField([
                                'name' => 'limitType',
                                'options' => [
                                    ['label' => Craft::t('formie', 'Characters'), 'value' => 'characters'],
                                    ['label' => Craft::t('formie', 'Words'), 'value' => 'words'],
                                ],
                            ]),
                        ],
                    ],
                ],
            ],
            SchemaHelper::matchField([
                'fieldTypes' => [self::class],
            ]),
            SchemaHelper::prePopulate(),
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

        $id = $this->getHtmlId($form);
        $dataId = $this->getHtmlDataId($form);

        if ($key === 'fieldInput') {
            $limitType = $this->limitType ?? '';
            $limitAmount = $this->limitAmount ?? null;
            $limit = ($this->limit ?? null) && $limitAmount;
            $maxLength = ($limit && $limitType === 'characters') ? $limitAmount : null;
            $wordLimit = ($limit && $limitType === 'words') ? $limitAmount : null;

            return new HtmlTag('input', array_merge([
                'type' => 'text',
                'id' => $id,
                'class' => 'fui-input',
                'name' => $this->getHtmlName(),
                'placeholder' => Craft::t('site', $this->placeholder) ?: null,
                'required' => $this->required ? true : null,
                'maxlength' => $maxLength ?: null,
                'data' => [
                    'fui-id' => $dataId,
                    'fui-message' => Craft::t('site', $this->errorMessage) ?: null,
                    'wordlimit' => $wordLimit ?: null,
                ],
                'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
            ], $this->getInputAttributes()));
        }

        if ($key === 'fieldLimit') {
            return new HtmlTag('div', [
                'class' => 'fui-limit-text',
                'data-max-limit' => true,
            ]);
        }

        return parent::defineHtmlTag($key, $context);
    }


    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['limitAmount'], 'number', 'integerOnly' => true];
        $rules[] = [['limitType'], 'in', 'range' => ['characters', 'words']];

        return $rules;
    }

}
