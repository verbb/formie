<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\base\FormField;
use verbb\formie\elements\Form;
use verbb\formie\helpers\SchemaHelper;

use Craft;
use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;
use craft\helpers\Json;
use craft\helpers\Template;

use yii\db\Schema;
use LitEmoji\LitEmoji;
use Twig\Markup;

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
            $limitAmount = $this->limitAmount ?? false;
            $maxlength = ($limitType == 'characters') ? $limitAmount : '';
            $wordlimit = ($limitType == 'words') ? $limitAmount : '';

            if ($maxlength) {
                $rules[] = 'validateMaxCharacters';
            }

            if ($wordlimit) {
                $rules[] = 'validateMaxWords';
            }
        }

        return $rules;
    }

    /**
     * Validates the maximum number of characters.
     *
     * @param ElementInterface $element
     */
    public function validateMaxCharacters(ElementInterface $element)
    {
        $limitAmount = intval($this->limitAmount ?? 0);

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
     */
    public function validateMaxWords(ElementInterface $element)
    {
        $limitAmount = intval($this->limitAmount ?? 0);

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
    public function getIsTextInput(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
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
            'field' => $this
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getFrontEndJsModules()
    {
        if ($this->limit) {
            return [
                'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/js/fields/text-limit.js', true),
                'module' => 'FormieTextLimit',
            ];
        }
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
            SchemaHelper::toggleContainer('settings.required', [
                SchemaHelper::textField([
                    'label' => Craft::t('formie', 'Error Message'),
                    'help' => Craft::t('formie', 'When validating the form, show this message if an error occurs. Leave empty to retain the default message.'),
                    'name' => 'errorMessage',
                ]),
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Limit Field Content'),
                'help' => Craft::t('formie', 'Whether to limit the content of this field.'),
                'name' => 'limit',
            ]),
            SchemaHelper::toggleContainer('settings.limit', [
                [
                    'label' => Craft::t('formie', 'Limit'),
                    'help' => Craft::t('formie', 'Enter the number of characters or words to limit this field by.'),
                    'type' => 'fieldWrap',
                    'children' => [
                        [
                            'component' => 'div',
                            'class' => 'flex',
                            'children' => [
                                SchemaHelper::textField([
                                    'name' => 'limitAmount',
                                    'class' => 'text flex-grow',
                                    'size' => '3',
                                    'validation' => 'optional|number|min:0',
                                ]),
                                SchemaHelper::selectField([
                                    'name' => 'limitType',
                                    'options' => [
                                        [ 'label' => Craft::t('formie', 'Characters'), 'value' => 'characters' ],
                                        [ 'label' => Craft::t('formie', 'Words'), 'value' => 'words' ],
                                    ],
                                ]),
                            ],
                        ],
                    ],
                ]
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

    /**
     * @inheritDoc
     */
    public function defineConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }
}
