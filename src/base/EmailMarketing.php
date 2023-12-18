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


    // Public Methods
    // =========================================================================

    public function getIconUrl(): string
    {
        $handle = $this->getClassHandle();

        return Craft::$app->getAssetManager()->getPublishedUrl("@verbb/formie/web/assets/cp/dist/img/emailmarketing/{$handle}.svg", true);
    }

    /**
     * @inheritDoc
     */
    public function getSettingsHtml(): ?string
    {
        $handle = $this->getClassHandle();

        return Craft::$app->getView()->renderTemplate("formie/integrations/email-marketing/{$handle}/_plugin-settings", [
            'integration' => $this,
        ]);
    }

    public function getFormSettingsHtml($form): string
    {
        $handle = $this->getClassHandle();

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
