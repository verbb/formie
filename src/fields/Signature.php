<?php
namespace verbb\formie\fields;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\base\PreviewableFieldInterface;
use verbb\formie\elements\Submission;
use verbb\formie\fields\definitions\FieldBrowserModules;
use verbb\formie\fields\definitions\FieldReferenceValue;
use verbb\formie\fields\definitions\FieldValueType;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\SignatureAccess;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\BrowserModuleEntry;
use verbb\formie\models\SlotTag;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Notification;
use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\ElementInterface;
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

    public function valueType(): FieldValueType
    {
        return FieldValueType::string();
    }

    public function fieldKind(): string
    {
        return self::KIND_SIGNATURE;
    }

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewSignature(),
        ];
    }

    public function getImageUrl(Submission $submission, mixed $value)
    {
        // If `devMode` is on, assume local development, and use base64 as image
        if (App::devMode()) {
            return $value;
        }

        $accessToken = SignatureAccess::issueAccessToken($submission, (int)$this->id, $this->valueKey(), $value);

        if ($accessToken) {
            return UrlHelper::actionUrl('formie/fields/get-signature-image', [
                'accessToken' => $accessToken,
            ]);
        }

        if ($submission->usesLegacySignatureAccess() && Formie::$plugin->getSettings()->allowLegacySignatureImageUrls) {
            return UrlHelper::actionUrl('formie/fields/get-signature-image', [
                'submissionUid' => $submission->uid,
                'fieldId' => $this->id,
            ]);
        }

        return null;
    }

    public function getDownloadUrl(Submission $submission): ?string
    {
        $value = $submission->getFieldValue($this->valueKey());
        $accessToken = SignatureAccess::issueAccessToken($submission, (int)$this->id, $this->valueKey(), $value);

        if ($accessToken) {
            return StringHelper::sanitizeUrlAttribute(UrlHelper::actionUrl('formie/fields/get-signature-image', [
                'accessToken' => $accessToken,
            ]));
        }

        if ($submission->usesLegacySignatureAccess() && Formie::$plugin->getSettings()->allowLegacySignatureImageUrls) {
            return StringHelper::sanitizeUrlAttribute(UrlHelper::actionUrl('formie/fields/get-signature-image', [
                'submissionUid' => $submission->uid,
                'fieldId' => $this->id,
            ]));
        }

        return null;
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

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::colorField([
                'label' => Craft::t('formie', 'Background Color'),
                'instructions' => Craft::t('formie', 'Set the background color.'),
                'name' => 'backgroundColor',
            ]),
            SchemaHelper::colorField([
                'label' => Craft::t('formie', 'Pen Color'),
                'instructions' => Craft::t('formie', 'Set the pen color.'),
                'name' => 'penColor',
            ]),
            SchemaHelper::fieldWrap([
                'label' => Craft::t('formie', 'Pen Weight'),
                'instructions' => Craft::t('formie', 'Set the line thickness (weight) for the pen.'),
                'children' => [
                    SchemaHelper::numberField([
                        'name' => 'penWeight',
                    ]),
                    [
                        '$el' => 'span',
                        'attrs' => ['class' => 'text-sm text-gray-300'],
                        'children' => Craft::t('formie', 'px'),
                    ],
                ],
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
            SchemaHelper::requiredField(),
            SchemaHelper::requiredValidationMessage(),
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

    // Protected Methods
    // =========================================================================

    protected function defineSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        if ($key === 'fieldInput') {
            return SlotTag::make('input')
                ->core([
                    'type' => 'hidden',
                    'name' => $this->getHtmlName(),
                    'data-formie-signature-input' => true,
                ])
                ->instanceAttributes($this->getInputAttributes());
        }

        if ($key === 'fieldCanvas') {
            return SlotTag::make('canvas')
                ->core([
                    'data-formie-signature-canvas' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-signature-canvas',
                    ],
                ]);
        }

        if ($key === 'fieldMessage') {
            return SlotTag::make('p')
                ->core([
                    'data-formie-signature-message' => true,
                    'data-formie-signature-message-no-canvas' => Craft::t('formie', 'This browser does not support canvas, which is required for signatures.'),
                    'data-formie-signature-message-init-failed' => Craft::t('formie', 'The signature pad could not be loaded. Try refreshing the page.'),
                    'role' => 'status',
                    'hidden' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-signature-message',
                    ],
                ]);
        }

        if ($key === 'fieldRemoveButton') {
            return SlotTag::make('button')
                ->core([
                    'type' => 'button',
                    'aria-label' => Craft::t('formie', 'Clear signature'),
                    'title' => Craft::t('formie', 'Clear signature'),
                    'data-formie-icon' => 'close',
                    'data-formie-signature-clear' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-button',
                        'formie-button-icon',
                        'formie-signature-remove-button',
                    ],
                ]);
        }

        return parent::defineSlotTag($key, $context);
    }

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        $downloadUrl = null;

        if ($element instanceof Submission && is_string($value) && $value !== '') {
            $downloadUrl = $this->getDownloadUrl($element);
        }

        return Craft::$app->getView()->renderTemplate('formie/_formfields/signature/input', [
            'value' => $value,
            'field' => $this,
            'element' => $element,
            'downloadUrl' => $downloadUrl,
        ]);
    }

    protected function defineValueForSummary(mixed $value, ElementInterface $element = null): mixed
    {
        if (!$element instanceof Submission) {
            return '';
        }

        $url = StringHelper::sanitizeUrlAttribute((string)$this->getImageUrl($element, $value));

        if (!$url) {
            return '';
        }

        return Template::raw(Html::tag('img', null, ['src' => $url]));
    }

    protected function defineBrowserModules(): array
    {
        $modules = parent::defineBrowserModules();
        
        $modules[] = new BrowserModuleEntry([
            'moduleId' => 'formie:signature',
            'surfaces' => [BrowserModuleEntry::SURFACE_SERVER_RENDERED, BrowserModuleEntry::SURFACE_CLIENT_RENDERED, BrowserModuleEntry::SURFACE_CP_EDIT],
            'config' => [
                'backgroundColor' => $this->backgroundColor,
                'penColor' => $this->penColor,
                'penWeight' => $this->penWeight,
            ],
        ]);

        return $modules;
    }

    protected function defineReferenceValues(): array
    {
        return [
            FieldReferenceValue::default([
                'variableTypes' => [Variables::TYPE_URL],
            ]),
            FieldReferenceValue::property([
                'handle' => 'url',
                'label' => Craft::t('formie', 'Image URL'),
                'variableTypes' => [Variables::TYPE_URL],
            ]),
        ];
    }
}
