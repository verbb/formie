<?php
namespace verbb\formie\fields;

use verbb\formie\base\Field;
use verbb\formie\base\PreviewableFieldInterface;
use verbb\formie\base\SortableFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyFieldValueEvent;
use verbb\formie\fields\definitions\FieldValueType;
use verbb\formie\helpers\HiddenDefaultTemplateResolver;
use verbb\formie\helpers\References;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\BrowserModule;
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

    public const DEFAULT_OPTION_TEMPLATE = 'template';

    public ?string $valueSource = 'custom';
    public ?string $defaultTemplate = null;
    public ?string $queryParameter = null;
    public ?string $cookieName = null;


    // Public Methods
    // =========================================================================

    public function valueType(): FieldValueType
    {
        return FieldValueType::string();
    }

    public function __construct(array $config = [])
    {
        if (array_key_exists('defaultOption', $config)) {
            $config['valueSource'] = $config['valueSource'] ?? $config['defaultOption'];
            unset($config['defaultOption']);
        }
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

    public function getDefaultOption(): ?string
    {
        return $this->valueSource;
    }

    public function setDefaultOption(?string $source): void
    {
        $this->valueSource = $source;
    }

    public function isAuthoritativeSource(): bool
    {
        return in_array($this->valueSource, ['template', 'dateUs', 'dateInt', 'userId', 'username', 'userEmail', 'userIp'], true);
    }

    public function runtimeOverridableSettings(): array
    {
        return [...parent::runtimeOverridableSettings(), 'valueSource', 'defaultTemplate', 'queryParameter', 'cookieName'];
    }

    public function getIsHidden(): bool
    {
        return true;
    }

    public function usesTemplateDefault(): bool
    {
        return $this->valueSource === self::DEFAULT_OPTION_TEMPLATE;
    }

    public function getDefaultValue(): mixed
    {
        if (!$this->usesTemplateDefault()) {
            $request = Craft::$app->getRequest();
            $user = Craft::$app->getUser()->getIdentity();
            $web = !$request->getIsConsoleRequest();
            return match ($this->valueSource) {
                'dateUs' => (new DateTime())->format('m/d/Y'),
                'dateInt' => (new DateTime())->format('d/m/Y'),
                'userId' => $user?->id === null ? null : (string)$user->id,
                'username' => $user?->username,
                'userEmail' => $user?->email,
                'userIp' => $web ? $request->getUserIP() : null,
                'userAgent' => $web ? $request->getUserAgent() : null,
                'referUrl' => $web ? $request->getReferrer() : null,
                'currentUrl' => $web ? $request->getAbsoluteUrl() : null,
                'currentUrlNoQueryString' => $web ? UrlHelper::stripQueryString($request->getAbsoluteUrl()) : null,
                'query' => $web && $this->queryParameter ? $request->getQueryParam($this->queryParameter) : null,
                'cookie' => $web && $this->cookieName ? $request->getCookies()->getValue($this->cookieName) : null,
                default => parent::getDefaultValue(),
            };
        }

        $form = $this->getForm();
        $element = $form?->getCurrentSubmission() ?? $form;

        return $this->_finalizeTemplateDefaultValue(
            HiddenDefaultTemplateResolver::resolve($this, $element),
        );
    }

    public function getInitialValue(?ElementInterface $element = null): mixed
    {
        $prefillValue = $this->getPrefillValue($element, $found);

        if ($found) {
            return $prefillValue;
        }

        if ($this->usesTemplateDefault()) {
            return $this->_finalizeTemplateDefaultValue(
                HiddenDefaultTemplateResolver::resolve($this, $element ?? $this->getForm()),
            );
        }

        $value = $this->getDefaultValue();
        if ($this->valueSource === 'custom' && $element instanceof Submission && is_string($value)) {
            return References::parseContent($value, $element);
        }
        return $value;
    }

    public function normalizeValueFromRequest(mixed $value, ?ElementInterface $element): mixed
    {
        if ($this->isAuthoritativeSource()) {
            $value = $this->getDefaultValue();
        }

        return parent::normalizeValueFromRequest($value, $element);
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
        } else if ($this->usesTemplateDefault()) {
            $inputOptions['value'] = $this->getInitialValue($submission ?: $form);
        }

        return $inputOptions;
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'valueSource' => [
                'name' => 'valueSource',
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
            'defaultTemplate' => [
                'name' => 'defaultTemplate',
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
                'name' => 'valueSource',
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
                    ['label' => Craft::t('formie', 'Template'), 'value' => self::DEFAULT_OPTION_TEMPLATE],
                ],
            ]),
            SchemaHelper::objectTemplateField([
                'label' => Craft::t('formie', 'Default Template'),
                'instructions' => Craft::t('formie', 'Set a server-resolved default using Craft object template syntax. Submitted values are ignored for this field. See [object templates](https://craftcms.com/docs/5.x/system/object-templates.html). Available: `{form.handle}`, `{form.title}`, `{currentUser.email}`, `{site.handle}`, `{request.param.myParam}`, `{submission.id}`.'),
                'name' => 'defaultTemplate',
                'if' => 'valueSource == "template"',
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
                'if' => 'valueSource == "custom"',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Query Parameter'),
                'instructions' => Craft::t('formie', 'Entering the query parameter to populate the value of the field when it loads.'),
                'name' => 'queryParameter',
                'if' => 'valueSource == "query"',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Cookie Name'),
                'instructions' => Craft::t('formie', 'Enter the name of the cookie to use as the value of this field.'),
                'name' => 'cookieName',
                'if' => 'valueSource == "cookie"',
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

    protected function defineRules(): array
    {
        return [...parent::defineRules(), [['valueSource'], 'in', 'range' => ['custom', 'template', 'dateUs', 'dateInt', 'userId', 'username', 'userEmail', 'userIp', 'userAgent', 'referUrl', 'currentUrl', 'currentUrlNoQueryString', 'query', 'cookie']]];
    }

    protected function supportedDefaults(): array
    {
        return ['valueSource', 'defaultTemplate'];
    }

    protected function defineValueForCondition(mixed $value, Submission $submission): mixed
    {
        // Prevent an infinite loop with hidden fields, as their `serializeValue()` will call this
        return $this->getValueAsString($value, $submission);
    }

    protected function defineSlotTag(string $key, RenderContext $context): ?SlotTag
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

        return parent::defineSlotTag($key, $context);
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

    protected function defineClientRenderedInput(): array
    {
        return array_merge(parent::defineClientRenderedInput(), [
            'valueSource' => $this->valueSource,
            'queryParameter' => $this->queryParameter,
            'cookieName' => $this->cookieName,
            'inputType' => 'hidden',
        ]);
    }

    protected function defineBrowserModules(): array
    {
        $modules = parent::defineBrowserModules();

        if ($this->valueSource === 'cookie' && $this->cookieName) {
            $modules[] = new BrowserModule([
                'moduleId' => 'formie:hidden',
                'surfaces' => [BrowserModule::SURFACE_SERVER_RENDERED, BrowserModule::SURFACE_CLIENT_RENDERED],
                'config' => [
                    'cookieName' => $this->cookieName,
                ],
            ]);
        }

        return $modules;
    }


    // Private Methods
    // =========================================================================

    private function _finalizeTemplateDefaultValue(mixed $value): mixed
    {
        $value = $this->normalizeValue($value, null);

        $event = new ModifyFieldValueEvent([
            'value' => $value,
            'field' => $this,
        ]);

        $this->trigger(static::EVENT_MODIFY_DEFAULT_VALUE, $event);

        if (is_string($event->value)) {
            $event->value = trim($event->value);
        }

        return $event->value;
    }
}
