<?php
namespace verbb\formie\base;

use verbb\formie\base\FormInterface;
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

abstract class Crm extends Integration
{
    // Static Methods
    // =========================================================================

    public static function typeName(): string
    {
        return Craft::t('formie', 'CRM');
    }


    // Public Methods
    // =========================================================================

    public function getType(): string
    {
        return self::TYPE_CRM;
    }

    public function getCategory(): string
    {
        return self::CATEGORY_CRM;
    }

    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('formie/integrations/crm/edit/' . $this->id);
    }

    public function getIconUrl(): string
    {
        $handle = $this->getClassHandle();

        return Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/cp/dist/', true, "icons/crm/{$handle}.svg");
    }

    public function getSettingsHtml(): ?string
    {
        $handle = $this->getClassHandle();
        $variables = $this->getSettingsHtmlVariables();

        return Craft::$app->getView()->renderTemplate("formie/integrations/crm/{$handle}/_plugin-settings", $variables);
    }

    public function supportsFormSettingsRefresh(): bool
    {
        return true;
    }

    public function getFieldMappingValues(Submission $submission, ?array $fieldMapping, mixed $fieldSettings = [])
    {
        // A quick shortcut to keep CRM's simple, just pass in a string to the namespace
        if (is_string($fieldSettings)) {
            $fields = $this->getFormSettingValue($fieldSettings);
        } else {
            $fields = $fieldSettings;
        }

        return parent::getFieldMappingValues($submission, $fieldMapping, $fields);
    }


    // Protected Methods
    // =========================================================================

    protected function defineFormSettingsSchema(FormInterface $form): array
    {
        $schema = parent::defineFormSettingsSchema($form);
        $schema[] = $this->getOptInFieldSchema();

        return $schema;
    }

    protected function getCollectionOptions(string $settingsKey): array
    {
        $options = [
            [
                'label' => Craft::t('formie', 'Select an option'),
                'value' => '',
            ],
        ];

        $collections = $this->getFormSettingValue($settingsKey);

        if (!is_array($collections)) {
            return $options;
        }

        foreach ($collections as $collection) {
            $id = is_array($collection) ? ($collection['id'] ?? null) : ($collection->id ?? null);
            $name = is_array($collection) ? ($collection['name'] ?? null) : ($collection->name ?? null);

            if ($id === null || $name === null) {
                continue;
            }

            $options[] = [
                'label' => (string)$name,
                'value' => (string)$id,
            ];
        }

        return $options;
    }
}
