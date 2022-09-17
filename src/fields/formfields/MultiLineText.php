<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormField;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\HtmlTag;

use Craft;
use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;

use GraphQL\Type\Definition\Type;

use yii\db\Schema;

use LitEmoji\LitEmoji;

class MultiLineText extends FormField implements PreviewableFieldInterface
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Multi-line Text');
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/multi-line-text/icon.svg';
    }


    // Properties
    // =========================================================================

    public bool $limit = false;
    public ?string $limitType = null;
    public ?int $limitAmount = null;
    public bool $useRichText = false;
    public ?array $richTextButtons = null;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getContentColumnType(): array|string
    {
        return Schema::TYPE_TEXT;
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        if ($value !== null) {
            $value = LitEmoji::entitiesToUnicode($value);
        }

        return $value !== '' ? $value : null;
    }

    /**
     * @inheritdoc
     */
    public function serializeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        if ($value !== null) {
            // Save as HTML entities (e.g. `&#x1F525;`) so we can use that in JS to determine length.
            // Saving as a shortcode is too tricky to detemine the same length in JS.
            $value = LitEmoji::encodeHtml($value);
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        $form = null;

        if ($element instanceof Submission) {
            $form = $element->getForm();
        }

        return Craft::$app->getView()->renderTemplate('formie/_formfields/multi-line-text/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
            'form' => $form,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/multi-line-text/preview', [
            'field' => $this,
        ]);
    }

    public function getFrontEndJsModules(): ?array
    {
        $modules = [];

        if ($this->limit) {
            $modules[] = [
                'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/js/fields/text-limit.js', true),
                'module' => 'FormieTextLimit',
            ];
        }

        if ($this->useRichText) {
            $modules[] = [
                'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/js/fields/rich-text.js', true),
                'module' => 'FormieRichText',
                'settings' => [
                    'buttons' => $this->getRichTextButtons(),
                ],
            ];
        }

        return $modules;
    }

    public function getRichTextButtons()
    {
        $order = array_map(function($item) {
            return $item['value'];
        }, $this->getButtonOptions());

        // Return the order of buttons as they were defined in our field
        if ($this->richTextButtons) {
            usort($this->richTextButtons, function ($a, $b) use ($order) {
                $pos_a = array_search($a, $order);
                $pos_b = array_search($b, $order);

                return $pos_a - $pos_b;
            });
        }

        return $this->richTextButtons;
    }

    /**
     * @inheritDoc
     */
    public function getFieldDefaults(): array
    {
        return [
            'richTextButtons' => ['bold', 'italic'],
        ];
    }

    public function getButtonOptions()
    {
        return [
            ['label' => Craft::t('formie', 'Bold'), 'value' => 'bold'],
            ['label' => Craft::t('formie', 'Italic'), 'value' => 'italic'],
            ['label' => Craft::t('formie', 'Underline'), 'value' => 'underline'],
            ['label' => Craft::t('formie', 'Strike-through'), 'value' => 'strikethrough'],
            ['label' => Craft::t('formie', 'Heading 1'), 'value' => 'heading1'],
            ['label' => Craft::t('formie', 'Heading 2'), 'value' => 'heading2'],
            ['label' => Craft::t('formie', 'Paragraph'), 'value' => 'paragraph'],
            ['label' => Craft::t('formie', 'Quote'), 'value' => 'quote'],
            ['label' => Craft::t('formie', 'Ordered List'), 'value' => 'olist'],
            ['label' => Craft::t('formie', 'Unordered List'), 'value' => 'ulist'],
            ['label' => Craft::t('formie', 'Code'), 'value' => 'code'],
            ['label' => Craft::t('formie', 'Horizontal Rule'), 'value' => 'line'],
            ['label' => Craft::t('formie', 'Link'), 'value' => 'link'],
            ['label' => Craft::t('formie', 'Image'), 'value' => 'image'],
            ['label' => Craft::t('formie', 'Align Left'), 'value' => 'alignleft'],
            ['label' => Craft::t('formie', 'Align Center'), 'value' => 'aligncenter'],
            ['label' => Craft::t('formie', 'Align Right'), 'value' => 'alignright'],
            ['label' => Craft::t('formie', 'Clear Formatting'), 'value' => 'clear'],
        ];
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'richTextButtons' => [
                'name' => 'richTextButtons',
                'type' => Type::listOf(Type::string()),
            ],
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
                'label' => Craft::t('formie', 'Placeholder'),
                'help' => Craft::t('formie', 'The text that will be shown if the field doesn’t have a value.'),
                'name' => 'placeholder',
            ]),
            SchemaHelper::textareaField([
                'label' => Craft::t('formie', 'Default Value'),
                'help' => Craft::t('formie', 'Entering a default value will place the value in the field when it loads.'),
                'name' => 'defaultValue',
                'rows' => '3',
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
                'label' => Craft::t('formie', 'Limit Field Value'),
                'help' => Craft::t('formie', 'Whether to limit the value of this field.'),
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
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Use Rich Text Field'),
                'help' => Craft::t('formie', 'Whether to display this field with a rich text editor for users to enter values with.'),
                'name' => 'useRichText',
            ]),
            SchemaHelper::checkboxSelectField([
                'label' => Craft::t('formie', 'Rich Text Buttons'),
                'help' => Craft::t('formie', 'Select which formatting buttons available for users to use.'),
                'name' => 'richTextButtons',
                'showAllOption' => false,
                'if' => '$get(useRichText).value',
                'options' => $this->getButtonOptions(),
            ]),
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
        $errors = $context['errors'] ?? null;

        $id = $this->getHtmlId($form);
        $dataId = $this->getHtmlDataId($form);

        if ($key === 'fieldInput') {
            $limitType = $this->limitType ?? '';
            $limitAmount = $this->limitAmount ?? null;
            $limit = ($this->limit ?? null) && $limitAmount;
            $maxLength = ($limit && $limitType === 'characters') ? $limitAmount : null;
            $wordLimit = ($limit && $limitType === 'words') ? $limitAmount : null;

            return new HtmlTag('textarea', array_merge([
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
                    'fui-message' => Craft::t('formie', $this->errorMessage) ?: null,
                    'maxlength' => $maxLength ?: null,
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

        if ($key === 'fieldRichText') {
            return new HtmlTag('div', [
                'class' => 'fui-rich-text',
                'data-rich-text' => true,
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
