<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormField;
use verbb\formie\helpers\SchemaHelper;

use Craft;
use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;
use craft\db\Query;
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
    public $uniqueValue = false;


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

        if ($this->validateDomain) {
            $rules[] = [$this->handle, EmailValidator::class, 'skipOnEmpty' => true, 'checkDNS' => true];
        }

        if ($this->blockedDomains && is_array($this->blockedDomains)) {
            $rules[] = 'validateDomain';
        }

        if ($this->uniqueValue) {
            $rules[] = 'validateUniqueEmail';
        }

        return $rules;
    }

    /**
     * @inheritDoc
     */
    public function validateDomain(ElementInterface $element)
    {
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
    public function validateUniqueEmail(ElementInterface $element)
    {
        $value = $element->getFieldValue($this->handle);
        $value = trim($value);

        // Use a DB lookup for performance
        $fieldHandle = $element->fieldColumnPrefix . $this->handle;
        $contentTable = $element->contentTable;

        // TODO: remove schema version condition after next beakpoint
        if (version_compare(Craft::$app->getInstalledSchemaVersion(), '3.7.0', '>=')) {
            if ($this->columnSuffix) {
                $fieldHandle .= '_' . $this->columnSuffix;
            }
        }

        // Be sure to check only against completed submission content
        $emailExists = (new Query())
            ->select($fieldHandle)
            ->from(['c' => $contentTable])
            ->where([$fieldHandle => $value, 'isIncomplete' => false])
            ->leftJoin(['s' => '{{%formie_submissions}}'], "[[s.id]] = [[c.elementId]]")
            ->exists();

        if ($emailExists) {
            $element->addError($this->handle, Craft::t('formie', '“{name}” must be unique.', [
                'name' => $this->name,
            ]));
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
            SchemaHelper::inputAttributesField(),
            SchemaHelper::enableContentEncryptionField(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }
}
