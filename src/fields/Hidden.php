<?php
namespace verbb\formie\fields;

use verbb\formie\base\Field;
use verbb\formie\base\PreviewableFieldInterface;
use verbb\formie\base\SortableFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\definitions\FieldClientModules;
use verbb\formie\fields\values\StringFieldValue;
use verbb\formie\helpers\References;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\ClientModule;
use verbb\formie\positions\Hidden as HiddenPosition;
use verbb\formie\models\SlotTag;
use verbb\formie\models\Notification;

use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\DateTimeHelper;
use craft\helpers\UrlHelper;

use GraphQL\Type\Definition\Type;

use DateTime;

class Hidden extends Field implements SortableFieldInterface, PreviewableFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Hidden Field');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/hidden-field/icon.svg';
    }

    public function themeConfigKey(): string
    {
        return 'hiddenField';
    }


    // Properties
    // =========================================================================

    public ?string $defaultOption = 'custom';
    public ?string $queryParameter = null;
    public ?string $cookieName = null;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        // Remove unused settings
        unset($config['columnType']);

        // Setuo defaults for some values which can't in in the property definition
        $config['labelPosition'] = $config['labelPosition'] ?? HiddenPosition::class;

        parent::__construct($config);
    }

    public function fieldKind(): string
    {
        return self::KIND_HIDDEN;
    }

    public function init(): void
    {
        parent::init();

        $currentUser = Craft::$app->getUser()->getIdentity();
        $request = Craft::$app->getRequest();

        if (!Craft::$app->getRequest()->getIsConsoleRequest()) {
            if ($this->defaultOption === 'dateUs') {
                $this->defaultValue = DateTimeHelper::toDateTime(new DateTime())->format('m/d/Y');
            } else if ($this->defaultOption === 'dateInt') {
                $this->defaultValue = DateTimeHelper::toDateTime(new DateTime())->format('d/m/Y');
            } else if ($this->defaultOption === 'userAgent') {
                $this->defaultValue = $request->getUserAgent();
            } else if ($this->defaultOption === 'referUrl') {
                $this->defaultValue = $request->getReferrer();
            } else if ($this->defaultOption === 'currentUrl') {
                $this->defaultValue = $request->getAbsoluteUrl();
            } else if ($this->defaultOption === 'currentUrlNoQueryString') {
                $this->defaultValue = UrlHelper::stripQueryString($request->getAbsoluteUrl());
            } else if ($this->defaultOption === 'userId') {
                $this->defaultValue = $currentUser->id ?? null;
            } else if ($this->defaultOption === 'username') {
                $this->defaultValue = $currentUser->username ?? null;
            } else if ($this->defaultOption === 'userEmail') {
                $this->defaultValue = $currentUser->email ?? null;
            } else if ($this->defaultOption === 'userIp') {
                $this->defaultValue = $request->getUserIP();
            } else if ($this->defaultOption === 'query' && $this->queryParameter) {
                $this->defaultValue = $request->getParam($this->queryParameter);
            } else if ($this->defaultOption === 'cookie' && $this->cookieName) {
                $this->defaultValue = $_COOKIE[$this->cookieName] ?? '';
            }
        }
    }

    public function getIsHidden(): bool
    {
        return true;
    }

    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        // Handle variables use in custom fields
        if ($this->defaultOption === 'custom') {
            // Only field-authored defaults may resolve references. Non-empty submitted
            // Hidden values are attacker-controlled and must remain literal.
            if ($value === '') {
                $value = References::parseContent((string)$this->defaultValue, $element);
            }

            // Immediately update the value for the element, so integrations use the up-to-date value
            $element?->setFieldValue($this->handle, $value);
        }

        return parent::serializeValue($value, $element);
    }

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewInput([
                'type' => 'hidden',
                'wrapperClassName' => 'formie-field-preview-control formie-field-preview-control--hidden',
            ]),
        ];
    }

    public function getInputTemplateVariables(Form $form, mixed $value): array
    {
        $inputOptions = parent::getInputTemplateVariables($form, $value);
        $submission = $form->getCurrentSubmission();
        $prefillValue = $this->getPrefillValue($submission ?: $form, $hasPrefill);

        // Hidden initial values are treated as literal data at render time.
        // Only field-authored custom defaults resolve reference tokens, and only
        // when a submission context exists. Template/query prefills remain literal.
        if ($hasPrefill) {
            $inputOptions['value'] = $prefillValue;
        } else if ($this->defaultOption === 'custom' && is_string($value) && $submission) {
            $inputOptions['value'] = References::parseContent($value, $submission);
        }

        return $inputOptions;
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'defaultOption' => [
                'name' => 'defaultOption',
                'type' => Type::string(),
            ],
            'queryParameter' => [
                'name' => 'queryParameter',
                'type' => Type::string(),
            ],
            'cookieName' => [
                'name' => 'cookieName',
                'type' => Type::string(),
            ],
        ]);
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField([
                'label' => Craft::t('formie', 'Name'),
                'instructions' => Craft::t('formie', 'The name of this field displayed only to you'),
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Default Value'),
                'instructions' => Craft::t('formie', 'Select an option for the default value.'),
                'name' => 'defaultOption',
                'options' => [
                    ['label' => Craft::t('formie', 'Date (mm/dd/yyyy)'), 'value' => 'dateUs'],
                    ['label' => Craft::t('formie', 'Date (dd/mm/yyyy)'), 'value' => 'dateInt'],
                    ['label' => Craft::t('formie', 'Current URL'), 'value' => 'currentUrl'],
                    ['label' => Craft::t('formie', 'Current URL (without Query String)'), 'value' => 'currentUrlNoQueryString'],
                    ['label' => Craft::t('formie', 'HTTP User Agent'), 'value' => 'userAgent'],
                    ['label' => Craft::t('formie', 'HTTP Refer URL'), 'value' => 'referUrl'],
                    ['label' => Craft::t('formie', 'User ID'), 'value' => 'userId'],
                    ['label' => Craft::t('formie', 'Username'), 'value' => 'username'],
                    ['label' => Craft::t('formie', 'User Email'), 'value' => 'userEmail'],
                    ['label' => Craft::t('formie', 'User IP Address'), 'value' => 'userIp'],
                    ['label' => Craft::t('formie', 'Cookie Value'), 'value' => 'cookie'],
                    ['label' => Craft::t('formie', 'Query Parameter'), 'value' => 'query'],
                    ['label' => Craft::t('formie', 'Custom Value'), 'value' => 'custom'],
                ],
            ]),
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'Default Value'),
                'instructions' => Craft::t('formie', 'Set a default value for the field when it doesn’t have a value.'),
                'name' => 'defaultValue',
                'variableConfig' => [
                    'content' => Variables::CONTENT_SINGLE_LINE,
                    'types' => [Variables::TYPE_TEXT],
                    'groups' => [
                        Variables::STATIC_FORM,
                        Variables::STATIC_GENERAL,
                        Variables::STATIC_SITE,
                    ],
                ],
                'if' => 'defaultOption == "custom"',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Query Parameter'),
                'instructions' => Craft::t('formie', 'Entering the query parameter to populate the value of the field when it loads.'),
                'name' => 'queryParameter',
                'if' => 'defaultOption == "query"',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Cookie Name'),
                'instructions' => Craft::t('formie', 'Enter the name of the cookie to use as the value of this field.'),
                'name' => 'cookieName',
                'if' => 'defaultOption == "cookie"',
            ]),
        ];
    }

    public function defineFormBuilderSettingsSchema(): array
    {
        return [
            SchemaHelper::includeInEmailFieldSummariesField(),
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

    // Protected Methods
    // =========================================================================

    protected function defineValueForCondition(mixed $value, Submission $submission): mixed
    {
        // Prevent an infinite loop with hidden fields, as their `serializeValue()` will call this
        return $this->getValueAsString($value, $submission);
    }

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $form = $context->form;

        $id = $this->getHtmlId($form);
        $dataId = $this->getHtmlDataId($form);

        if ($key === 'fieldLabel') {
            return null;
        }

        if ($key === 'fieldInput') {
            return SlotTag::make('input')
                ->core([
                    'type' => 'hidden',
                    'id' => $id,
                    'name' => $this->getHtmlName(),
                    'data-formie-input' => true,
                    'data-formie-hidden-input' => true,
                    'data-formie-input-id' => $dataId,
                    'data-formie-input-type' => 'hidden',
                ])
                ->theme([
                    'class' => [
                        'formie-input',
                        'formie-hidden-input',
                    ],
                ])
                ->instanceAttributes($this->getInputAttributes());
        }

        return parent::defineFieldSlotTag($key, $context);
    }

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/hidden-field/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
        ]);
    }

    protected function supportsPlainTextHtmlSanitization(): bool
    {
        return true;
    }

    protected function defineClientInput(): array
    {
        return array_merge(parent::defineClientInput(), [
            'defaultOption' => $this->defaultOption,
            'queryParameter' => $this->queryParameter,
            'cookieName' => $this->cookieName,
            'inputType' => 'hidden',
        ]);
    }

    protected function defineClientModules(): array
    {
        $modules = parent::defineClientModules();

        if ($this->defaultOption === 'cookie' && $this->cookieName) {
            $modules[] = new ClientModule([
                'id' => 'hidden',
                'renderTargets' => [ClientModule::RENDER_TARGET_FRONTEND],
                'config' => [
                    'cookieName' => $this->cookieName,
                ],
            ]);
        }

        return $modules;
    }

    protected function defineValueClass(): ?string
    {
        return StringFieldValue::class;
    }
}
