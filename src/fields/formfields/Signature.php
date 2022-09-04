<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormField;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\HtmlTag;

use Craft;
use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;
use craft\db\mysql\Schema;
use craft\helpers\App;
use craft\helpers\Html;
use craft\helpers\Template;
use craft\helpers\UrlHelper;

class Signature extends FormField implements PreviewableFieldInterface
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Signature');
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/signature/icon.svg';
    }


    // Properties
    // =========================================================================

    public string $backgroundColor = '#ffffff';
    public string $penColor = '#000000';
    public string $penWeight = '2';


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getContentColumnType(): array|string
    {
        return Schema::TYPE_MEDIUMTEXT;
    }

    /**
     * @inheritDoc
     */
    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        return Html::tag('img', null, ['src' => $value]);
    }

    /**
     * @inheritDoc
     */
    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/signature/preview', [
            'field' => $this,
        ]);
    }

    public function getFrontEndJsModules(): ?array
    {
        return [
            'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/js/fields/signature.js', true),
            'module' => 'FormieSignature',
            'settings' => [
                'backgroundColor' => $this->backgroundColor,
                'penColor' => $this->penColor,
                'penWeight' => $this->penWeight,
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getFieldDefaults(): array
    {
        return [
            'backgroundColor' => '#ffffff',
            'penColor' => '#000000',
            'penWeight' => '2',
        ];
    }

    public function getImageUrl(Submission $submission, mixed $value)
    {
        // If `devMode` is on, assume local development, and use base64 as image
        if (App::devMode()) {
            return $value;
        }

        // On non-dev sites, use a proxy to serve the "image" so web-based clients work
        return UrlHelper::actionUrl('formie/fields/get-signature-image', [
            'submissionUid' => $submission->uid,
            'fieldId' => $this->id,
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
                '$formkit' => 'color',
                'label' => Craft::t('formie', 'Background Color'),
                'help' => Craft::t('formie', 'Set the background color.'),
                'name' => 'backgroundColor',
                'size' => '4',
                'inputClass' => 'text fui-color-field',
            ]),
            SchemaHelper::textField([
                '$formkit' => 'color',
                'label' => Craft::t('formie', 'Pen Color'),
                'help' => Craft::t('formie', 'Set the pen color.'),
                'name' => 'penColor',
                'size' => '4',
                'inputClass' => 'text fui-color-field',
            ]),
            SchemaHelper::numberField([
                'label' => Craft::t('formie', 'Pen Weight'),
                'help' => Craft::t('formie', 'Set the line thickness (weight) for the pen.'),
                'name' => 'penWeight',
                'sections-schema' => [
                    'suffix' => [
                        '$el' => 'span',
                        'attrs' => ['class' => 'fui-suffix-text'],
                        'children' => Craft::t('formie', 'px'),
                    ],
                ],
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

    public function defineHtmlTag(string $key, array $context = []): ?HtmlTag
    {
        if ($key === 'fieldInput') {
            return new HtmlTag('input', array_merge([
                'type' => 'hidden',
                'name' => $this->getHtmlName(),
            ], $this->getInputAttributes()));
        }

        if ($key === 'fieldCanvas') {
            return new HtmlTag('canvas');
        }

        if ($key === 'fieldRemoveButton') {
            return new HtmlTag('button', [
                'class' => 'fui-btn fui-signature-clear-btn',
                'data-signature-clear' => true,
                'type' => 'button',
            ]);
        }

        return parent::defineHtmlTag($key, $context);
    }


    // Protected Methods
    // =========================================================================

    protected function defineValueForSummary($value, ElementInterface $element = null): string
    {
        return Template::raw(Html::tag('img', null, ['src' => $value]));
    }
}
