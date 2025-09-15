<?php
namespace verbb\formie\fields;

use verbb\formie\base\Field;
use verbb\formie\elements\Submission;
use verbb\formie\gql\types\generators\FieldAttributeGenerator;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\HtmlTag;

use Craft;
use craft\base\ElementInterface;
use craft\base\InlineEditableFieldInterface;
use craft\base\PreviewableFieldInterface;
use craft\base\SortableFieldInterface;
use craft\db\Query;

use Faker\Generator as FakerFactory;

use GraphQL\Type\Definition\Type;

use yii\validators\EmailValidator;

class Email extends Field implements InlineEditableFieldInterface, PreviewableFieldInterface, SortableFieldInterface
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

    public static function supportsIdn(): bool
    {
        return function_exists('idn_to_ascii') && defined('INTL_IDNA_VARIANT_UTS46');
    }


    // Properties
    // =========================================================================

    public bool $validateDomain = false;
    public array $blockedDomains = [];
    public bool $uniqueValue = false;


    // Public Methods
    // =========================================================================

    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();

        // Enable base validations
        $rules[] = ['trim'];
        $rules[] = ['email', 'enableIDN' => self::supportsIdn(), 'enableLocalIDN' => false];

        if ($this->validateDomain) {
            $rules[] = [$this->handle, EmailValidator::class, 'skipOnEmpty' => true, 'checkDNS' => true];
        }

        if ($this->blockedDomains) {
            $rules[] = 'validateDomain';
        }

        if ($this->uniqueValue) {
            $rules[] = 'validateUniqueValue';
        }

        return $rules;
    }

    public function validateDomain(ElementInterface $element): void
    {
        $blockedDomains = ArrayHelper::getColumn($this->blockedDomains, 'value');

        $value = $element->getFieldValue($this->fieldKey);

        $domain = explode('@', $value)[1] ?? null;

        if ($domain) {
            $domain = trim($domain);

            if (in_array($domain, $blockedDomains)) {
                $element->addError($this->fieldKey, Craft::t('formie', '“{domain}” is not allowed.', [
                    'domain' => $domain,
                ]));
            }
        }
    }

    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/email/preview', [
            'field' => $this,
        ]);
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
                'help' => Craft::t('formie', 'Set a default value for the field when it doesn’t have a value.'),
                'name' => 'defaultValue',
                'variables' => 'userVariables',
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
            SchemaHelper::matchField([
                'fieldTypes' => [self::class],
            ]),
            SchemaHelper::prePopulate(),
            SchemaHelper::includeInEmailField(),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Unique Value'),
                'help' => Craft::t('formie', 'Whether to limit user input to unique values only. This will require that a value entered in this field does not already exist in a submission for this field and form.'),
                'name' => 'uniqueValue',
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Validate Domain (DNS)'),
                'help' => Craft::t('formie', 'Whether to validate the domain name provided for the email via DNS record lookup. This can help ensure users enter valid email addresses.'),
                'name' => 'validateDomain',
            ]),
            SchemaHelper::tableField([
                'label' => Craft::t('formie', 'Blocked Domains'),
                'help' => Craft::t('formie', 'Define a list of domain names to block. Users entering email addresses containing these domains will be blocked from using them.'),
                'name' => 'blockedDomains',
                'validation' => '',
                'newRowDefaults' => [
                    'domain' => '',
                ],
                'columns' => [
                    [
                        'type' => 'label',
                        'label' => Craft::t('formie', 'Domain'),
                        'class' => 'singleline-cell textual',
                    ],
                ],
            ]),
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
            return new HtmlTag('input', [
                'type' => 'email',
                'id' => $id,
                'class' => [
                    'fui-input',
                    $errors ? 'fui-error' : false,
                ],
                'name' => $this->getHtmlName(),
                'placeholder' => Craft::t('formie', $this->placeholder) ?: null,
                'autocomplete' => 'email',
                'required' => $this->required ? true : null,
                'data' => [
                    'fui-id' => $dataId,
                    'required-message' => Craft::t('formie', $this->errorMessage) ?: null,
                ],
                'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
            ], $this->getInputAttributes());
        }

        return parent::defineHtmlTag($key, $context);
    }


    // Protected Methods
    // =========================================================================

    protected function cpInputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
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
}
