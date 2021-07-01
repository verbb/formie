<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationField;

use Craft;
use craft\fields;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;

abstract class Element extends Integration implements IntegrationInterface
{
    // Properties
    // =========================================================================

    public $attributeMapping;
    public $fieldMapping;
    public $updateElement = false;
    public $updateElementMapping;


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function typeName(): string
    {
        return Craft::t('formie', 'Elements');
    }

    /**
     * @inheritDoc
     */
    public static function supportsConnection(): bool
    {
        return false;
    }


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getIconUrl(): string
    {
        $handle = StringHelper::toKebabCase($this->displayName());

        return Craft::$app->getAssetManager()->getPublishedUrl("@verbb/formie/web/assets/elements/dist/img/{$handle}.svg", true);
    }

    /**
     * @inheritDoc
     */
    public function getSettingsHtml(): string
    {
        $handle = StringHelper::toKebabCase($this->displayName());

        return Craft::$app->getView()->renderTemplate("formie/integrations/elements/{$handle}/_plugin-settings", [
            'integration' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getFormSettingsHtml($form): string
    {
        $handle = StringHelper::toKebabCase($this->displayName());

        return Craft::$app->getView()->renderTemplate("formie/integrations/elements/{$handle}/_form-settings", [
            'integration' => $this,
            'form' => $form,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('formie/settings/elements/edit/' . $this->id);
    }

    /**
     * @inheritDoc
     */
    public function getFormSettings($useCache = true)
    {
        // Always fetch, no real need for cache
        return $this->fetchFormSettings();
    }


    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected function getFieldTypeForField($fieldClass)
    {
        // Provide a map of all native Craft fields to the data we expect
        $fieldTypeMap = [
            fields\Assets::class => IntegrationField::TYPE_ARRAY,
            fields\Categories::class => IntegrationField::TYPE_ARRAY,
            fields\Checkboxes::class => IntegrationField::TYPE_ARRAY,
            fields\Date::class => IntegrationField::TYPE_DATETIME,
            fields\Entries::class => IntegrationField::TYPE_ARRAY,
            fields\Lightswitch::class => IntegrationField::TYPE_BOOLEAN,
            fields\MultiSelect::class => IntegrationField::TYPE_ARRAY,
            fields\Number::class => IntegrationField::TYPE_FLOAT,
            fields\Tags::class => IntegrationField::TYPE_ARRAY,
            fields\Users::class => IntegrationField::TYPE_ARRAY,
        ];

        return $fieldTypeMap[$fieldClass] ?? IntegrationField::TYPE_STRING;
    }

    /**
     * @inheritDoc
     */
    protected function fieldCanBeUniqueId($field)
    {
        $type = get_class($field);

        $supportedFields = [
            fields\Checkboxes::class,
            fields\Color::class,
            fields\Date::class,
            fields\Dropdown::class,
            fields\Email::class,
            fields\Lightswitch::class,
            fields\MultiSelect::class,
            fields\Number::class,
            fields\PlainText::class,
            fields\RadioButtons::class,
            fields\Url::class,
        ];

        if (in_array($type, $supportedFields, true)) {
            return true;
        }

        // Include any field types that extend one of the above
        foreach ($supportedFields as $supportedField) {
            if (is_a($type, $supportedField, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    protected function getElementForPayload($elementType, $submission)
    {
        $element = new $elementType();

        // Check if configuring update, and find an existing element, depending on mapping
        $updateElementValues = $this->getFieldMappingValues($submission, $this->updateElementMapping, $this->getUpdateAttributes());
        $updateElementValues = array_filter($updateElementValues);

        if ($updateElementValues) {
            $query = $elementType::find($updateElementValues);

            Craft::configure($query, $updateElementValues);

            if ($foundElement = $query->one()) {
                $element = $foundElement;
            }
        }

        return $element;
    }
}
