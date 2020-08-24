<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\EmailMarketingList;
use verbb\formie\models\IntegrationField;

use Craft;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;

abstract class EmailMarketing extends Integration implements IntegrationInterface
{
    // Properties
    // =========================================================================

    public $listId;
    public $optInField;
    public $fieldMapping;

    
    // Static Methods
    // =========================================================================
    
    /**
     * @inheritDoc
     */
    public static function typeName(): string
    {
        return Craft::t('formie', 'Email Marketing');
    }
    

    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getIconUrl(): string
    {
        $handle = StringHelper::toKebabCase($this->displayName());

        return Craft::$app->getAssetManager()->getPublishedUrl("@verbb/formie/web/assets/emailmarketing/dist/img/{$handle}.svg", true);
    }

    /**
     * @inheritDoc
     */
    public function getSettingsHtml(): string
    {
        $handle = StringHelper::toKebabCase($this->displayName());

        return Craft::$app->getView()->renderTemplate("formie/integrations/email-marketing/{$handle}/_plugin-settings", [
            'integration' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getFormSettingsHtml(Form $form): string
    {
        $handle = StringHelper::toKebabCase($this->displayName());

        return Craft::$app->getView()->renderTemplate("formie/integrations/email-marketing/{$handle}/_form-settings", [
            'integration' => $this,
            'form' => $form,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('formie/settings/email-marketing/edit/' . $this->id);
    }

    /**
     * @inheritDoc
     */
    public function validateFieldMapping($attribute)
    {
        if ($this->enabled) {
            // Ensure we check against any required fields
            $list = $this->getListById($this->listId);

            foreach ($list->fields as $field) {
                $value = $this->fieldMapping[$field->handle] ?? '';

                if ($field->required && $value === '') {
                    $this->addError($attribute, Craft::t('formie', '{name} must be mapped.', ['name' => $field->name]));
                    return;
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        // Validate the following when saving form settings
        $rules[] = [['listId'], 'required', 'on' => [Integration::SCENARIO_FORM]];
        $rules[] = [['fieldMapping'], 'validateFieldMapping', 'on' => [Integration::SCENARIO_FORM]];

        return $rules;
    }


    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected function getListById($listId)
    {
        $settings = $this->getFormSettings();
        $lists = $settings['lists'] ?? [];

        foreach ($lists as $listConfig) {
            $list = new EmailMarketingList($listConfig);

            if ($list->id === $listId) {
                // De-serialize JSON array data from cache. Might be a better way to do this?
                foreach ($list->fields as $key => $fieldConfig) {
                    $list->fields[$key] = new IntegrationField($fieldConfig);
                }

                return $list;
            }
        }

        return new EmailMarketingList();
    }

    /**
     * @inheritDoc
     */
    protected function getFieldMappingValues(Submission $submission)
    {
        $fieldValues = [];

        foreach ($this->fieldMapping as $tag => $formFieldHandle) {
            if ($formFieldHandle) {
                $formFieldHandle = str_replace(['{', '}'], ['', ''], $formFieldHandle);

                // Convert to string. We'll introduce more complex field handling in the future, but this will
                // be controlled at the integration-level. Some providers might only handle an address as a string
                // others might accept an array of content. The integration should handle this...
                $fieldValues[$tag] = (string)$submission->getFieldValue($formFieldHandle);
            }
        }

        return $fieldValues;
    }

    /**
     * @inheritDoc
     */
    protected function enforceOptInField(Submission $submission)
    {
        // Default is just always do it!
        if (!$this->optInField) {
            return true;
        }

        $fieldValues = $this->getFieldMappingValues($submission);
        $fieldValue = $fieldValues[$this->optInField] ?? null;

        if ($fieldValue === null) {
            Integration::log($this, Craft::t('formie', 'Unable to find field “{field}” for opt-in in submission.', [
                'field' => $this->optInField,
            ]));

            return false;
        }

        // Just a simple 'falsey' check is good enough
        if (!$fieldValue) {
            Integration::log($this, Craft::t('formie', 'Opting-out. Field “{field}” has value “{value}”.', [
                'field' => $this->optInField,
                'value' => $fieldValue,
            ]));

            return false;
        }

        return true;
    }
}
