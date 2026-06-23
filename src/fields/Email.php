<?php
namespace verbb\formie\fields;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\base\PreviewableFieldInterface;
use verbb\formie\base\SortableFieldInterface;
use verbb\formie\elements\Submission;
use verbb\formie\fields\definitions\FieldReferenceValue;
use verbb\formie\fields\values\EmailFieldValue;
use verbb\formie\gql\types\generators\FieldAttributeGenerator;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\fields\traits\UniqueValueFieldTrait;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\ValidationMessagesHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\SlotTag;

use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\ElementInterface;
use craft\db\Query;

use Faker\Generator as FakerFactory;

use GraphQL\Type\Definition\Type;

use yii\validators\EmailValidator;

class Email extends Field implements SortableFieldInterface, PreviewableFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Email Address');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/email/icon.svg';
    }

    public static function supportsGqlConfigProvider(): bool
    {
        return true;
    }

    public static function supportsIdn(): bool
    {
        return function_exists('idn_to_ascii') && defined('INTL_IDNA_VARIANT_UTS46');
    }

    public static function compatibleFieldTypes(): array
    {
        return [
            SingleLineText::class,
        ];
    }

    // Traits
    // =========================================================================

    use UniqueValueFieldTrait;


    // Properties
    // =========================================================================

    public bool $validateDomain = false;
    public array $blockedDomains = [];
    public bool $blockFreeDomains = false;


    // Public Methods
    // =========================================================================

    public function themeConfigKey(): string
    {
        return 'emailAddress';
    }

    public function fieldKind(): string
    {
        return self::KIND_TEXT;
    }

    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();

        $rules[] = [$this->handle, 'email', 'enableIDN' => self::supportsIdn(), 'enableLocalIDN' => false];

        if ($this->validateDomain) {
            $rules[] = [$this->handle, EmailValidator::class, 'skipOnEmpty' => true, 'checkDNS' => true];
        }

        if ($this->blockedDomains) {
            $rules[] = [$this->handle, 'validateDomain'];
        }

        if ($this->blockFreeDomains) {
            $rules[] = [$this->handle, 'validateFreeDomain'];
        }

        foreach ($this->getUniqueValueElementValidationRules() as $rule) {
            $rules[] = $rule;
        }

        return $rules;
    }

    public function validateDomain(ElementInterface $element): void
    {
        $emailDomains = Formie::$plugin->getEmailDomains();
        $value = $element->getFieldValue($this->valueKey());
        $domain = $emailDomains->extractDomainFromEmail((string)$value);

        if (!$domain) {
            return;
        }

        $blockedDomains = array_filter(array_map(function($blockedDomain) use ($emailDomains) {
            return $emailDomains->normalizeDomain((string)$blockedDomain);
        }, ArrayHelper::getColumn($this->blockedDomains, 'value')));

        if (in_array($domain, $blockedDomains, true)) {
            $element->addError($this->valueKey(), $this->getValidationMessage(ValidationMessagesHelper::KEY_BLOCKED_DOMAIN, [
                'domain' => $domain,
            ]));
        }
    }

    public function validateFreeDomain(ElementInterface $element): void
    {
        $emailDomains = Formie::$plugin->getEmailDomains();
        $value = $element->getFieldValue($this->valueKey());
        $domain = $emailDomains->extractDomainFromEmail((string)$value);

        if (!$domain) {
            return;
        }

        if ($emailDomains->isFreeDomain($domain)) {
            $element->addError($this->valueKey(), $this->getValidationMessage(ValidationMessagesHelper::KEY_BLOCKED_DOMAIN, [
                'domain' => $domain,
            ]));
        }
    }

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewInput([
                'type' => 'email',
            ]),
        ];
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'blockedDomains' => [
                'name' => 'blockedDomains',
                'type' => Type::listOf(Type::string()),
                'resolve' => function($field) {
                    return array_map(function($item) {
                        return $item['value'] ?? '';
                    }, $field->blockedDomains);
                },
            ],
        ]);
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Placeholder'),
                'instructions' => Craft::t('formie', 'The text that will be shown if the field doesn’t have a value.'),
                'name' => 'placeholder',
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
            ]),
        ];
    }

    public function defineFormBuilderSettingsSchema(): array
    {
        return [
            SchemaHelper::prePopulate(),
            SchemaHelper::includeInEmailFieldSummariesField(),
        ];
    }

    public function defineFormBuilderValidationSchema(): array
    {
        return [
            SchemaHelper::requiredField(),
            SchemaHelper::requiredValidationMessage(),
            SchemaHelper::matchField([
                'includedTypes' => [self::class],
            ]),
            SchemaHelper::matchValidationMessage(),
            ...$this->defineUniqueValueValidationSchema(),
            SchemaHelper::validationMessageField([
                'messageKey' => ValidationMessagesHelper::KEY_EMAIL,
                'name' => 'validationMessages.email',
                'tokens' => ['label'],
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Validate Domain (DNS)'),
                'instructions' => Craft::t('formie', 'Whether to validate the domain name provided for the email via DNS record lookup. This can help ensure users enter valid email addresses.'),
                'name' => 'validateDomain',
            ]),
            SchemaHelper::tableField([
                'label' => Craft::t('formie', 'Blocked Domains'),
                'instructions' => Craft::t('formie', 'Define a list of domain names to block. Users entering email addresses containing these domains will be blocked from using them.'),
                'name' => 'blockedDomains',
                'columns' => [
                    [
                        'type' => 'text',
                        'name' => 'label',
                        'label' => Craft::t('formie', 'Domain'),
                        'required' => true,
                    ],
                ],
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Block Free Email Providers'),
                'instructions' => Craft::t('formie', 'Whether to block email addresses from free email providers like `gmail.com` or `hotmail.com`.'),
                'name' => 'blockFreeDomains',
            ]),
            SchemaHelper::validationMessageField([
                'messageKey' => ValidationMessagesHelper::KEY_BLOCKED_DOMAIN,
                'name' => 'validationMessages.blockedDomain',
                'tokens' => ['domain'],
            ]),
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
            SchemaHelper::inputAttributesField(),
            SchemaHelper::enableContentEncryptionField(),
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

    protected function supportedDefaults(): array
    {
        return ['validateDomain', 'blockFreeDomains'];
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        foreach ($this->defineUniqueValueRules() as $rule) {
            $rules[] = $rule;
        }

        return $rules;
    }

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $form = $context->form;
        $errors = $context->errors;

        $id = $this->getHtmlId($form);
        $dataId = $this->getHtmlDataId($form);

        if ($key === 'fieldInput') {
            return SlotTag::make('input')
                ->core(array_merge([
                    'type' => 'email',
                    'id' => $id,
                    'name' => $this->getHtmlName(),
                    'placeholder' => $this->placeholder ?: null,
                    'autocomplete' => 'email',
                    'required' => $this->required ? true : null,
                    'data-formie-input' => true,
                    'data-formie-email-input' => true,
                    'data-formie-input-id' => $dataId,
                    'data-formie-input-type' => 'email',
                    'data-formie-input-error-state' => $errors ? true : false,
                    'aria-describedby' => $this->hasInstructions() ? "{$id}-instructions" : null,
                ], ValidationMessagesHelper::emailValidationClientAttributes($this)))
                ->theme([
                    'class' => [
                        'formie-input',
                        'formie-email-input',
                        $errors ? 'formie-input-error' : false,
                    ],
                ])
                ->instanceAttributes($this->getInputAttributes());
        }

        return parent::defineFieldSlotTag($key, $context);
    }

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/email/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
        ]);
    }

    protected function defineValueForEmailPreview(FakerFactory $faker): mixed
    {
        return $faker->email;
    }

    protected function defineReferenceValues(): array
    {
        return [
            FieldReferenceValue::default([
                'variableTypes' => [
                    Variables::TYPE_TEXT,
                    Variables::TYPE_EMAIL,
                ],
            ]),
        ];
    }

    protected function supportsPlainTextHtmlSanitization(): bool
    {
        return true;
    }

    protected function defineValidationRules(): array
    {
        $validators = parent::defineValidationRules();
        $validators[] = ['type' => 'email'];

        return $validators;
    }

    protected function defineClientInput(): array
    {
        return array_merge(parent::defineClientInput(), [
            'inputType' => 'email',
        ]);
    }

    protected function defineValueClass(): ?string
    {
        return EmailFieldValue::class;
    }
}
