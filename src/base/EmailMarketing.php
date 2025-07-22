<?php
namespace verbb\formie\base;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\SendIntegrationPayloadEvent;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\Stencil;

use Craft;
use craft\helpers\Html;
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

    public function getType(): string
    {
        return self::TYPE_EMAIL_MARKETING;
    }

    public function getCategory(): string
    {
        return self::CATEGORY_EMAIL_MARKETING;
    }

    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('formie/settings/email-marketing/edit/' . $this->id);
    }

    public function getIconUrl(): string
    {
        $handle = $this->getClassHandle();

        return Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/cp/dist/', true, "img/emailmarketing/{$handle}.svg");
    }

    public function getSettingsHtml(): ?string
    {
        $handle = $this->getClassHandle();
        $variables = $this->getSettingsHtmlVariables();

        return Craft::$app->getView()->renderTemplate("formie/integrations/email-marketing/{$handle}/_plugin-settings", $variables);
    }

    public function getFormSettingsHtml(Form|Stencil $form): string
    {
        $handle = $this->getClassHandle();
        $variables = $this->getFormSettingsHtmlVariables($form);

        return Craft::$app->getView()->renderTemplate("formie/integrations/email-marketing/{$handle}/_form-settings", $variables);
    }

    public function getFieldMappingValues(Submission $submission, ?array $fieldMapping, mixed $fieldSettings = [])
    {
        // A quick shortcut as all email marketing integrations are the same field mapping-wise
        $fields = $this->_getListSettings()->fields ?? [];

        return parent::getFieldMappingValues($submission, $fieldMapping, $fields);
    }

    public function getFrontEndJsVariables(FieldInterface $field = null): ?array
    {
        return null;
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
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
