<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\SendIntegrationPayloadEvent;
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
    public function getFormSettings($useCache = true)
    {
        $settings = parent::getFormSettings($useCache);

        // Convert back to models from the cache
        foreach ($settings as $key => $setting) {
            if ($key === 'lists') {
                foreach ($setting as $k => $listConfig) {
                    $list = new EmailMarketingList($listConfig);

                    foreach ($list->fields as $i => $fieldConfig) {
                        $list->fields[$i] = new IntegrationField($fieldConfig);
                    }

                    $settings[$key][$k] = $list;
                }
            }
        }

        return $settings;
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        // Validate the following when saving form settings
        $rules[] = [['listId'], 'required', 'on' => [Integration::SCENARIO_FORM]];

        $list = $this->getListById($this->listId);

        $rules[] = [['fieldMapping'], 'validateFieldMapping', 'params' => $list->fields, 'when' => function($model) {
            return $model->enabled;
        }, 'on' => [Integration::SCENARIO_FORM]];

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
    protected function beforeSendPayload(Submission $submission, $payload)
    {
        $event = new SendIntegrationPayloadEvent([
            'submission' => $submission,
            'payload' => $payload,
            'integration' => $this,
        ]);
        $this->trigger(self::EVENT_BEFORE_SEND_PAYLOAD, $event);

        if (!$event->isValid) {
            Integration::log($this, 'Sending payload cancelled by event hook.');
        }

        // Also, check for opt-in fields. This allows the above event to potentially alter things
        if (!$this->enforceOptInField($submission)) {
            Integration::log($this, 'Sending payload cancelled by opt-in field.');

            return false;
        }

        return $event->isValid;
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
