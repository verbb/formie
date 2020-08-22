<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\base\ThirdPartyIntegration;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\SendIntegrationPayloadEvent;
use verbb\formie\helpers\UrlHelper;
use verbb\formie\models\EmailMarketingList;

use Craft;
use craft\base\Model;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper as CraftUrlHelper;
use craft\web\Response;

use League\OAuth2\Client\Provider\GenericProvider;

abstract class EmailMarketing extends ThirdPartyIntegration
{
    // Properties
    // =========================================================================

    public $listId;
    public $optInField;
    public $fieldMapping;

    
    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getIconUrl(): string
    {
        $handle = StringHelper::toKebabCase($this->handle);

        return Craft::$app->getAssetManager()->getPublishedUrl("@verbb/formie/web/assets/emailmarketing/dist/img/{$handle}.svg", true);
    }

    /**
     * @inheritDoc
     */
    public function getSettingsHtml(): string
    {
        $handle = StringHelper::toKebabCase($this->handle);

        return Craft::$app->getView()->renderTemplate("formie/integrations/email-marketing/{$handle}/_plugin-settings", [
            'integration' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getFormSettingsHtml(Form $form): string
    {
        $handle = StringHelper::toKebabCase($this->handle);

        return Craft::$app->getView()->renderTemplate("formie/integrations/email-marketing/{$handle}/_form-settings", [
            'integration' => $this,
            'form' => $form,
        ]);
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

        $rules[] = [['listId'], 'required'];
        $rules[] = [['fieldMapping'], 'validateFieldMapping'];

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

        foreach ($lists as $list) {
            if ($list->id === $listId) {
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
