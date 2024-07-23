<?php
namespace verbb\formie\fields;

use verbb\formie\base\Field;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\HtmlTag;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Notification;

use Craft;
use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;
use craft\db\mysql\Schema;
use craft\helpers\App;
use craft\helpers\Html;
use craft\helpers\Template;
use craft\helpers\UrlHelper;

use GraphQL\Type\Definition\Type;

class Signature extends Field implements PreviewableFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Signature');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/signature/icon.svg';
    }

    public static function dbType(): string
    {
        return Schema::TYPE_MEDIUMTEXT;
    }


    // Properties
    // =========================================================================

    public string $backgroundColor = '#ffffff';
    public string $penColor = '#000000';
    public string $penWeight = '2';


    // Public Methods
    // =========================================================================

    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/signature/preview', [
            'field' => $this,
        ]);
    }

    public function getFrontEndJsModules(): ?array
    {
        return [
            'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/', true, 'js/fields/signature.js'),
            'module' => 'FormieSignature',
            'settings' => [
                'backgroundColor' => $this->backgroundColor,
                'penColor' => $this->penColor,
                'penWeight' => $this->penWeight,
            ],
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

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'backgroundColor' => [
                'name' => 'backgroundColor',
                'type' => Type::string(),
            ],
            'penColor' => [
                'name' => 'penColor',
                'type' => Type::string(),
            ],
            'penWeight' => [
                'name' => 'penWeight',
                'type' => Type::string(),
            ],
        ]);
    }

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
            SchemaHelper::includeInEmailField(),
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

    public function defineHtmlTag(string $key, array $context = []): ?HtmlTag
    {
        if ($key === 'fieldInput') {
            return new HtmlTag('input', [
                'type' => 'hidden',
                'name' => $this->getHtmlName(),
            ], $this->getInputAttributes());
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

    protected function cpInputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Html::tag('img', null, ['src' => $value]);
    }

    protected function defineValueForSummary(mixed $value, ElementInterface $element = null): string
    {
        return Template::raw(Html::tag('img', null, ['src' => $value]));
    }
}
