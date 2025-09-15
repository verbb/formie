<?php
namespace verbb\formie\fields;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\positions\Hidden as HiddenPosition;
use verbb\formie\models\HtmlTag;
use verbb\formie\models\Notification;

use Craft;
use craft\base\ElementInterface;
use craft\base\InlineEditableFieldInterface;
use craft\base\PreviewableFieldInterface;
use craft\base\SortableFieldInterface;
use craft\helpers\DateTimeHelper;
use craft\helpers\UrlHelper;
use craft\web\View;

use GraphQL\Type\Definition\Type;

use Throwable;
use DateTime;

class Hidden extends Field implements InlineEditableFieldInterface, PreviewableFieldInterface, SortableFieldInterface
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

        parent::__construct($config);
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

    public function getFieldTypeDefaults(): array
    {
        // Setup defaults for some values which can't be set in the property definition
        $settings = parent::getFieldTypeDefaults();
        $settings['labelPosition'] = HiddenPosition::class;

        return $settings;
    }

    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        // Handle variables use in custom fields
        if ($this->defaultOption === 'custom') {
            // Check if there's no value been added on the front-end, and use the default value
            if ($value === '') {
                $value = $this->defaultValue;
            }

            $value = Variables::getParsedValue($value, $element);

            // Immediately update the value for the element, so integrations use the up-to-date value
            $element?->setFieldValue($this->handle, $value);
        }

        return parent::serializeValue($value, $element);
    }

    public function getValueForCondition(mixed $value, Submission $submission): mixed
    {
        // Prevent an infinite loop with hidden fields, as their `serializeValue()` will call this
        return $this->getValueAsString($value, $submission);
    }

    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/hidden-field/preview', [
            'field' => $this,
        ]);
    }

    public function getFrontEndJsModules(): ?array
    {
        if ($this->defaultOption === 'cookie' && $this->cookieName) {
            return [
                'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/', true, 'js/fields/hidden.js'),
                'module' => 'FormieHidden',
                'settings' => [
                    'cookieName' => $this->cookieName,
                ],
            ];
        }

        return null;
    }

    public function getFrontEndInputOptions(Form $form, mixed $value, array $renderOptions = []): array
    {
        $inputOptions = parent::getFrontEndInputOptions($form, $value, $renderOptions);

        try {
            $defaultValue = Craft::$app->getView()->renderString(
                (string)$this->defaultValue,
                [
                    'field' => $this,
                    'form' => $form,
                ],
                View::TEMPLATE_MODE_SITE
            );
        } catch (Throwable $e) {
            $defaultValue = $this->defaultValue;
            Formie::error('Failed to render hidden field template: ' . $e->getMessage());
        }

        $inputOptions['defaultValue'] = $defaultValue;

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

    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField([
                'label' => Craft::t('formie', 'Name'),
                'help' => Craft::t('formie', 'The name of this field displayed only to you'),
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Default Value'),
                'help' => Craft::t('formie', 'Select an option for the default value.'),
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
                'help' => Craft::t('formie', 'Set a default value for the field when it doesn’t have a value.'),
                'name' => 'defaultValue',
                'variables' => 'plainTextVariables',
                'if' => '$get(defaultOption).value == custom',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Query Parameter'),
                'help' => Craft::t('formie', 'Entering the query parameter to populate the value of the field when it loads.'),
                'name' => 'queryParameter',
                'if' => '$get(defaultOption).value == query',
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Cookie Name'),
                'help' => Craft::t('formie', 'Enter the name of the cookie to use as the value of this field.'),
                'name' => 'cookieName',
                'if' => '$get(defaultOption).value == cookie',
            ]),
        ];
    }

    public function defineSettingsSchema(): array
    {
        return [
            SchemaHelper::includeInEmailField(),
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

    public function defineHtmlTag(string $key, array $context = []): ?HtmlTag
    {
        $form = $context['form'] ?? null;

        $id = $this->getHtmlId($form);
        $dataId = $this->getHtmlDataId($form);

        if ($key === 'fieldLabel') {
            return null;
        }

        if ($key === 'fieldInput') {
            return new HtmlTag('input', [
                'type' => 'hidden',
                'id' => $id,
                'name' => $this->getHtmlName(),
                'data' => [
                    'fui-id' => $dataId,
                ],
            ], $this->getInputAttributes());
        }

        return parent::defineHtmlTag($key, $context);
    }


    // Protected Methods
    // =========================================================================

    protected function cpInputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/hidden-field/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
        ]);
    }
}
