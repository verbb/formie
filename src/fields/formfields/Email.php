<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormField;
use verbb\formie\helpers\SchemaHelper;

use Craft;
use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;
use craft\helpers\ArrayHelper;

use yii\validators\EmailValidator;

class Email extends FormField implements PreviewableFieldInterface
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Email Address');
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/email/icon.svg';
    }


    // Properties
    // =========================================================================

    public $validateDomain = false;
    public $blockedDomains = [];


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getIsTextInput(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();

        $rules[] = [
            $this->handle,
            EmailValidator::class,
            'skipOnEmpty' => true,
            'checkDNS' => $this->validateDomain,
        ];

        $rules[] = 'validateDomain';

        return $rules;
    }

    /**
     * @inheritDoc
     */
    public function validateDomain(ElementInterface $element)
    {
        if (!$this->blockedDomains || !is_array($this->blockedDomains)) {
            return;
        }

        $blockedDomains = ArrayHelper::getColumn($this->blockedDomains, 'value');

        $value = $element->getFieldValue($this->handle);

        $domain = explode('@', $value)[1] ?? null;

        if ($domain) {
            $domain = trim($domain);

            if (in_array($domain, $blockedDomains)) {
                $element->addError($this->handle, Craft::t('formie', '“{domain}” is not allowed.', [
                    'domain' => $domain,
                ]));
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/email/input', [
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
        return Craft::$app->getView()->renderTemplate('formie/_formfields/email/preview', [
            'field' => $this
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
            SchemaHelper::prePopulate(),
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
                        'type' => 'value',
                        'label' => Craft::t('formie', 'Domain'),
                        'class' => 'singleline-cell textual',
                    ],
                ],
            ]),
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineAppearanceSchema(): array
    {
        return [
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
        ];
    }
}
