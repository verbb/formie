<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\attributes\FormIntegrationSetting;
use verbb\formie\elements\Submission;
use verbb\formie\errors\IntegrationStepException;
use verbb\formie\events\ModifyElementFieldsEvent;
use verbb\formie\events\ModifyElementMatchEvent;
use verbb\formie\events\ModifyFieldIntegrationValueEvent;
use verbb\formie\fields\Date;
use verbb\formie\fields\MultiLineText;
use verbb\formie\fields\SingleLineText;
use verbb\formie\fields\subfields\AddressCountry;
use verbb\formie\fields\Table;
use verbb\formie\fields\values\MultiOptionFieldValue;
use verbb\formie\fields\values\SingleOptionFieldValue;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationResult;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface as CraftFieldInterface;
use craft\fields\Assets;
use craft\fields\BaseOptionsField;
use craft\fields\BaseRelationField;
use craft\fields\Categories;
use craft\fields\Checkboxes;
use craft\fields\Color;
use craft\fields\Country;
use craft\fields\Date as CraftDate;
use craft\fields\Dropdown;
use craft\fields\Email;
use craft\fields\Entries;
use craft\fields\Lightswitch;
use craft\fields\MultiSelect;
use craft\fields\Number;
use craft\fields\PlainText;
use craft\fields\RadioButtons;
use craft\fields\Table as CraftTable;
use craft\fields\Tags;
use craft\fields\Time;
use craft\fields\Url;
use craft\fields\Users;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;


use DateTime;
use DateTimeZone;

use CommerceGuys\Addressing\Country\CountryRepository;

abstract class Element extends Integration implements DispatchableIntegrationInterface
{
    // Static Methods
    // =========================================================================

    public static function typeName(): string
    {
        return Craft::t('formie', 'Elements');
    }

    public static function supportsConnection(): bool
    {
        return false;
    }


    // Constants
    // =========================================================================

    public const EVENT_MODIFY_ELEMENT_FIELDS = 'modifyElementFields';
    public const EVENT_MODIFY_ELEMENT_MATCH = 'modifyElementMatch';


    // Traits
    // =========================================================================

    use DispatchableIntegrationTrait;


    // Properties
    // =========================================================================

    #[FormIntegrationSetting]
    public array $attributeMapping = [];
    #[FormIntegrationSetting]
    public array $fieldMapping = [];
    #[FormIntegrationSetting]
    public bool $updateElement = false;
    #[FormIntegrationSetting]
    public array $updateElementMapping = [];
    #[FormIntegrationSetting]
    public bool $updateSearchIndexes = true;
    #[FormIntegrationSetting]
    public bool $overwriteValues = false;


    // Public Methods
    // =========================================================================

    public function getType(): string
    {
        return self::TYPE_ELEMENT;
    }

    public function getCategory(): string
    {
        return self::CATEGORY_ELEMENTS;
    }

    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('formie/integrations/elements/edit/' . $this->id);
    }

    public function getIconUrl(): string
    {
        $handle = $this->getClassHandle();

        return Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/cp/dist/', true, "icons/elements/{$handle}.svg");
    }

    public function getSettingsHtml(): ?string
    {
        $handle = $this->getClassHandle();
        $variables = $this->getSettingsHtmlVariables();

        return Craft::$app->getView()->renderTemplate("formie/integrations/elements/{$handle}/_plugin-settings", $variables);
    }

    public function getConfig(bool $useCache = true): IntegrationConfig
    {
        // Always fetch, no real need for cache
        return $this->fetchConfig();
    }

    public function populateQueueJobContext($submission, $endpoint, $payload, $method, $contentType): void
    {
        if (!$this->getDeliveryAttemptUid()) {
            return;
        }

        $fields = [];

        // Add in custom fields with a bit more context
        if ($fieldLayout = $payload->getFieldLayout()) {
            foreach ($fieldLayout->getCustomFields() as $field) {
                $fields[] = [
                    'type' => get_class($field),
                    'handle' => $field->handle,
                    'value' => $payload->getFieldValue($field->handle),
                ];
            }
        }

        // Keep prepared element evidence on the attempt; queue jobs only carry its UID.
        parent::populateQueueJobContext($submission, $endpoint, [
            'elementType' => get_class($payload),
            'attributes' => $payload->getAttributes(),
            'fields' => $fields,
        ], $method, $contentType);
    }

    public function recordDispatchElement(ElementInterface $element): void
    {
        if (!$element->id) {
            return;
        }

        $this->_deliveryState->outputs = [
            'elementType' => get_class($element),
            'elementId' => (int)$element->id,
            'url' => method_exists($element, 'getUrl') ? (string)$element->getUrl() : null,
        ];

        if ($uid = $this->getDeliveryAttemptUid()) {
            Formie::$plugin->getDeliveryAttempts()->recordResource($uid, $this->_deliveryState->outputs);
        }
    }


    // Protected Methods
    // =========================================================================


    protected function modifyFieldMappingValue(ModifyFieldIntegrationValueEvent $event): void
    {
        $fieldClass = $event->integrationField->sourceType;

        // When mapping to an array field (e.g. Submission ID to Formie Submission field), ensure value is an array
        if ($event->integrationField->getType() === IntegrationField::TYPE_ARRAY && !is_array($event->value)) {
            $event->value = [$event->value];
        }

        // For rich-text enabled fields, retain the HTML (safely)
        if ($event->field instanceof MultiLineText || $event->field instanceof SingleLineText) {
            if (is_string($event->value)) {
                $event->value = StringHelper::htmlDecode($event->value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
            }
        }

        // For options-based fields, we might be using the label, which is valid for mapping to text fields or other values
        // but if mapping to a Craft options field with the same label/value pair - it needs to be the value.
        if ($event->field instanceof OptionsFieldInterface) {
            if (is_a($fieldClass, BaseOptionsField::class, true) || is_subclass_of($fieldClass, BaseOptionsField::class, true)) {
                // Check for some cases where it's options data
                if ($event->rawValue instanceof SingleOptionFieldValue) {
                    $event->value = $event->rawValue->value;
                } elseif ($event->rawValue instanceof MultiOptionFieldValue) {
                    $event->value = $event->rawValue->values();
                } else {
                    $event->value = $event->rawValue;
                }
            }
        }

        // For Date fields as a destination, convert to UTC from system time
        if ($event->integrationField->getType() === IntegrationField::TYPE_DATECLASS) {
            if ($event->value instanceof DateTime) {
                $timezone = new DateTimeZone(Craft::$app->getTimeZone());

                $event->value = DateTime::createFromFormat('Y-m-d H:i:s', $event->value->format('Y-m-d H:i:s'), $timezone);
            }
        }

        // If mapping from Formie Date/Time to Craft Time
        if (is_a($fieldClass, Time::class, true) && $event->field instanceof Date) {
            if (!($event->value instanceof DateTime)) {
                $timezone = new DateTimeZone(Craft::$app->getTimeZone());

                $event->value = new DateTime($event->value, $timezone);
            }
        }

        // Check if we're mapping to a Craft relations field
        if (is_a($fieldClass, BaseRelationField::class, true) || is_subclass_of($fieldClass, BaseRelationField::class, true)) {

            if (is_string($event->rawValue) && Json::isJsonObject($event->rawValue)) {
                $event->value = Json::decode($event->rawValue);
            }
        }

        // For Table fields with Date/Time destination columns, convert to UTC from system time
        if ($event->field instanceof Table) {
            $timezone = new DateTimeZone(Craft::$app->getTimeZone());

            foreach ($event->value as $rowKey => $row) {
                foreach ($row as $colKey => $column) {
                    if (is_array($column) && isset($column['date'])) {
                        $event->value[$rowKey][$colKey] = (new DateTime($column['date'], $timezone));
                    }
                }
            }
        }

        // Check for Formie Address Country to Craft Country fields
        if (is_a($fieldClass, Country::class, true) && $event->field instanceof AddressCountry) {
            // Field requires prefix as a value, so override
            if (is_string($event->value) && strlen($event->value) > 3) {
                $countryRepository = new CountryRepository();

                foreach ($countryRepository->getAll() as $country) {
                    if ($country->getName() === $event->value) {
                        $event->value = $country->getCountryCode();
                    }
                }
            }
        }
    }

    protected function defineFormSettingsSchema(FormInterface $form): array
    {
        $schema = parent::defineFormSettingsSchema($form);
        $schema[] = $this->getOptInFieldSchema();

        return $schema;
    }

    protected function getFieldTypeForField(string $fieldClass): string
    {
        // Provide a map of all native Craft fields to the data we expect
        $fieldTypeMap = [
            Assets::class => IntegrationField::TYPE_ARRAY,
            Categories::class => IntegrationField::TYPE_ARRAY,
            Checkboxes::class => IntegrationField::TYPE_ARRAY,
            CraftDate::class => IntegrationField::TYPE_DATECLASS,
            Entries::class => IntegrationField::TYPE_ARRAY,
            Lightswitch::class => IntegrationField::TYPE_BOOLEAN,
            MultiSelect::class => IntegrationField::TYPE_ARRAY,
            Number::class => IntegrationField::TYPE_FLOAT,
            CraftTable::class => IntegrationField::TYPE_ARRAY,
            Tags::class => IntegrationField::TYPE_ARRAY,
            Users::class => IntegrationField::TYPE_ARRAY,
        ];

        if (is_a($fieldClass, BaseRelationField::class, true) || is_subclass_of($fieldClass, BaseRelationField::class, true)) {
            return IntegrationField::TYPE_ARRAY;
        }

        return $fieldTypeMap[$fieldClass] ?? IntegrationField::TYPE_STRING;
    }

    protected function fieldCanBeUniqueId(CraftFieldInterface $field): bool
    {
        $type = $field::class;

        $supportedFields = [
            Checkboxes::class,
            Color::class,
            CraftDate::class,
            Dropdown::class,
            Email::class,
            Lightswitch::class,
            MultiSelect::class,
            Number::class,
            PlainText::class,
            RadioButtons::class,
            Url::class,
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

    protected function getFieldLayoutFields(?FieldLayout $fieldLayout): array
    {
        $fields = [];

        if ($fieldLayout) {
            foreach ($fieldLayout->getCustomFields() as $field) {
                $fieldClass = get_class($field);

                $fields[] = new IntegrationField([
                    'handle' => $field->handle,
                    'name' => $field->name,
                    'type' => $this->getFieldTypeForField($fieldClass),
                    'sourceType' => $fieldClass,
                    'required' => (bool)$field->required,
                ]);
            }
        }

        // Fire a 'modifyElementFields' event
        $event = new ModifyElementFieldsEvent([
            'fieldLayout' => $fieldLayout,
            'fields' => $fields,
        ]);
        $this->trigger(self::EVENT_MODIFY_ELEMENT_FIELDS, $event);

        return $event->fields;
    }

    protected function getElementForPayload(string $elementType, string $identifier, Submission $submission, array $criteria = []): ElementInterface
    {
        $element = $this->defineElementForPayload($elementType, $identifier, $submission, $criteria);

        // Fire a 'modifyElementMatch' event
        $event = new ModifyElementMatchEvent([
            'elementType' => $elementType,
            'identifier' => $identifier,
            'submission' => $submission,
            'criteria' => $criteria,
            'element' => $element,
        ]);
        $this->trigger(self::EVENT_MODIFY_ELEMENT_MATCH, $event);

        return $event->element;
    }

    protected function defineElementForPayload($elementType, $identifier, $submission, array $criteria = [])
    {
        if ($uid = $this->getDeliveryAttemptUid()) {
            $resource = Formie::$plugin->getDeliveryAttempts()->resource($uid);

            if (($resource['elementType'] ?? null) === $elementType && !empty($resource['elementId'])) {
                $saved = Craft::$app->getElements()->getElementById((int)$resource['elementId'], $elementType);

                if ($saved) {
                    return $saved;
                }
                throw new IntegrationStepException(IntegrationResult::unknown('created_element_unavailable'));
            }
        }
        $element = new $elementType();

        // If we're not wanting to update an element, no need to proceed finding one.
        if (!$this->updateElement) {
            return $element;
        }

        // Pick from the available update attributes, depending on the identifier picked (e.g. `entryTypeId`, etc).
        $updateAttributes = $this->getUpdateAttributes()[$identifier] ?? [];

        // Check if configuring update, and find an existing element, depending on mapping
        $updateElementValues = $this->getFieldMappingValues($submission, $this->updateElementMapping, $updateAttributes);
        $updateElementValues = array_filter($updateElementValues);

        // Something must be mapped in order to find an element, otherwise it'll just find any element for the criteria
        if (!$updateElementValues) {
            return $element;
        }

        // Merge in any extra criteria supplied by the element integration class
        $updateElementValues = array_merge($updateElementValues, $criteria);

        if ($updateElementValues) {
            $query = $elementType::find($updateElementValues);

            // Find elements of any status, like disabled
            $query->status(null);

            Craft::configure($query, $updateElementValues);

            if ($foundElement = $query->one()) {
                $element = $foundElement;
            }
        }

        return $element;
    }
}
