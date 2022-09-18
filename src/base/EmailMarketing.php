<?php
namespace verbb\formie\base;

use verbb\formie\elements\Submission;
use verbb\formie\events\SendIntegrationPayloadEvent;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;

use yii\helpers\Markdown;

abstract class EmailMarketing extends Integration
{
    // Static Methods
    // =========================================================================

    public static function typeName(): string
    {
        return Craft::t('formie', 'Email Marketing');
    }

    // Properties
    // =========================================================================

    public ?array $fieldMapping = null;
    public ?string $listId = null;
    public ?string $optInField = null;


    // Public Methods
    // =========================================================================

    public function getIconUrl(): string
    {
        $handle = StringHelper::toKebabCase(static::displayName());

        return Craft::$app->getAssetManager()->getPublishedUrl("@verbb/formie/web/assets/cp/dist/img/emailmarketing/{$handle}.svg", true);
    }

    /**
     * @inheritDoc
     */
    public function getSettingsHtml(): ?string
    {
        $handle = StringHelper::toKebabCase(static::displayName());

        return Craft::$app->getView()->renderTemplate("formie/integrations/email-marketing/{$handle}/_plugin-settings", [
            'integration' => $this,
        ]);
    }

    public function getFormSettingsHtml($form): string
    {
        $handle = StringHelper::toKebabCase(static::displayName());

        return Craft::$app->getView()->renderTemplate("formie/integrations/email-marketing/{$handle}/_form-settings", [
            'integration' => $this,
            'form' => $form,
        ]);
    }

    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('formie/settings/email-marketing/edit/' . $this->id);
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        // Validate the following when saving form settings
        $rules[] = [['listId'], 'required', 'on' => [Integration::SCENARIO_FORM]];

        $fields = $this->_getListSettings()->fields ?? [];

        $rules[] = [
            ['fieldMapping'], 'validateFieldMapping', 'params' => $fields, 'when' => function($model) {
                return $model->enabled;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }

    public function getFieldMappingValues(Submission $submission, $fieldMapping, $fieldSettings = [])
    {
        // A quick shortcut as all email marketing integrations are the same field mapping-wise
        $fields = $this->_getListSettings()->fields ?? [];

        return parent::getFieldMappingValues($submission, $fieldMapping, $fields);
    }

    public function beforeSendPayload(Submission $submission, &$endpoint, &$payload, &$method): bool
    {
        // If in the context of a queue. save the payload for debugging
        if ($this->getQueueJob()) {
            $this->getQueueJob()->payload = $payload;
        }

        $event = new SendIntegrationPayloadEvent([
            'submission' => $submission,
            'payload' => $payload,
            'endpoint' => $endpoint,
            'method' => $method,
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

        // Allow events to alter some props
        $payload = $event->payload;
        $endpoint = $event->endpoint;
        $method = $event->method;

        return $event->isValid;
    }

    /**
     * Returns the front-end JS variables.
     */
    public function getFrontEndJsVariables($field = null): ?array
    {
        return null;
    }


    // Private Methods
    // =========================================================================

    private function _getListSettings()
    {
        $lists = $this->getFormSettingValue('lists');

        if ($list = ArrayHelper::firstWhere($lists, 'id', $this->listId)) {
            return $list;
        }

        return [];
    }
}
