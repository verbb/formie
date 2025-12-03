<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyElementFieldsEvent;
use verbb\formie\events\ModifyElementMatchEvent;
use verbb\formie\events\ModifyFieldIntegrationValueEvent;
use verbb\formie\fields as FormieFields;
use verbb\formie\fields\subfields as FormieSubFields;
use verbb\formie\fields\data\MultiOptionsFieldData;
use verbb\formie\fields\data\OptionData;
use verbb\formie\fields\data\SingleOptionFieldData;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;
use verbb\formie\models\Stencil;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface as CraftFieldInterface;
use craft\fields as CraftFields;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;

use yii\base\Event;
use yii\helpers\Markdown;

use DateTime;
use DateTimeZone;

use CommerceGuys\Addressing\Country\CountryRepository;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;

abstract class Element extends Integration
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_ELEMENT_FIELDS = 'modifyElementFields';
    public const EVENT_MODIFY_ELEMENT_MATCH = 'modifyElementMatch';


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

    public static function convertValueForIntegration($value, $integrationField): mixed
    {
        // Won't be picked up in `EVENT_MODIFY_FIELD_MAPPING_VALUE` because it's not mapped to a field.
        if ($integrationField->getType() === IntegrationField::TYPE_ARRAY) {
            // Mostly for when mapping a Submission ID to a Formie Submission field. Probably needs a refactor?
            if (!is_array($value)) {
                return [$value];
            }
        }

        return parent::convertValueForIntegration($value, $integrationField);
    }


    // Properties
    // =========================================================================

    public array $attributeMapping = [];
    public array $fieldMapping = [];
    public bool $updateElement = false;
    public array $updateElementMapping = [];
    public bool $updateSearchIndexes = true;
    public bool $overwriteValues = false;


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        Event::on(self::class, self::EVENT_MODIFY_FIELD_MAPPING_VALUE, function(ModifyFieldIntegrationValueEvent $event) {
            $fieldClass = $event->integrationField->sourceType;

            // For rich-text enabled fields, retain the HTML (safely)
            if ($event->field instanceof FormieFields\MultiLineText || $event->field instanceof FormieFields\SingleLineText) {
                if (is_string($event->value)) {
                    $event->value = StringHelper::htmlDecode($event->value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
                }
            }

            // For options-based fields, we might be using the label, which is valid for mapping to text fields or other values
            // but if mapping to a Craft options field with the same label/value pair - it needs to be the value.
            if ($event->field instanceof OptionsFieldInterface) {
                if (is_a($fieldClass, CraftFields\BaseOptionsField::class, true) || is_subclass_of($fieldClass, CraftFields\BaseOptionsField::class, true)) {
                    // Check for some cases where it's options data
                    if ($event->rawValue instanceof SingleOptionFieldData) {
                        $event->value = $event->rawValue->value;
                    } else if ($event->rawValue instanceof MultiOptionsFieldData) {
                        $event->value = array_map(function($item) {
                            return $item->value;
                        }, (array)$event->rawValue);
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
            if (is_a($fieldClass, CraftFields\Time::class, true) && $event->field instanceof FormieFields\Date) {
                if (!($event->value instanceof DateTime)) {
                    $timezone = new DateTimeZone(Craft::$app->getTimeZone());

                    $event->value = new DateTime($event->value, $timezone);
                }
            }

            // Element fields should map 1-for-1
            if ($event->field instanceof ElementFieldInterface) {
                $event->value = $event->submission->getFieldValue($event->field->handle)->ids();
            }

            // Check if we're mapping to a Craft relations field
            if (is_a($fieldClass, fields\BaseRelationField::class, true) || is_subclass_of($fieldClass, fields\BaseRelationField::class, true)) {

                if (is_string($event->rawValue) && Json::isJsonObject($event->rawValue)) {
                    $event->value = Json::decode($event->rawValue);
                }
            }

            // For Table fields with Date/Time destination columns, convert to UTC from system time
            if ($event->field instanceof FormieFields\Table) {
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
            if (is_a($fieldClass, CraftFields\Country::class, true) && $event->field instanceof FormieSubFields\AddressCountry) {
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
        });
    }

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
        return UrlHelper::cpUrl('formie/settings/elements/edit/' . $this->id);
    }

    public function getIconUrl(): string
    {
        $handle = $this->getClassHandle();

        return Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/cp/dist/', true, "img/elements/{$handle}.svg");
    }

    public function getSettingsHtml(): ?string
    {
        $handle = $this->getClassHandle();
        $variables = $this->getSettingsHtmlVariables();

        return Craft::$app->getView()->renderTemplate("formie/integrations/elements/{$handle}/_plugin-settings", $variables);
    }

    public function getFormSettingsHtml(Form|Stencil $form): string
    {
        $handle = $this->getClassHandle();
        $variables = $this->getFormSettingsHtmlVariables($form);

        return Craft::$app->getView()->renderTemplate("formie/integrations/elements/{$handle}/_form-settings", $variables);
    }

    public function getFormSettings(bool $useCache = true): IntegrationFormSettings|bool
    {
        // Always fetch, no real need for cache
        return $this->fetchFormSettings();
    }

    public function populateQueueJobContext($submission, $endpoint, $payload, $method, $contentType): void
    {
        if (!$this->getQueueJob()) {
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

        // Ensure that we JSON-serialize element/field content to not upset the queue.
        $this->getQueueJob()->payload = Json::decode(Json::encode([
            'element' => $payload,
            'fields' => $fields,
        ]));
    }


    // Protected Methods
    // =========================================================================

    protected function getFieldTypeForField(string $fieldClass): string
    {
        // Provide a map of all native Craft fields to the data we expect
        $fieldTypeMap = [
            CraftFields\Assets::class => IntegrationField::TYPE_ARRAY,
            CraftFields\Categories::class => IntegrationField::TYPE_ARRAY,
            CraftFields\Checkboxes::class => IntegrationField::TYPE_ARRAY,
            CraftFields\Date::class => IntegrationField::TYPE_DATECLASS,
            CraftFields\Entries::class => IntegrationField::TYPE_ARRAY,
            CraftFields\Lightswitch::class => IntegrationField::TYPE_BOOLEAN,
            CraftFields\MultiSelect::class => IntegrationField::TYPE_ARRAY,
            CraftFields\Number::class => IntegrationField::TYPE_FLOAT,
            CraftFields\Table::class => IntegrationField::TYPE_ARRAY,
            CraftFields\Tags::class => IntegrationField::TYPE_ARRAY,
            CraftFields\Users::class => IntegrationField::TYPE_ARRAY,
        ];

        if (is_a($fieldClass, CraftFields\BaseRelationField::class, true) || is_subclass_of($fieldClass, CraftFields\BaseRelationField::class, true)) {
            return IntegrationField::TYPE_ARRAY;
        }

        return $fieldTypeMap[$fieldClass] ?? IntegrationField::TYPE_STRING;
    }

    protected function fieldCanBeUniqueId(CraftFieldInterface $field): bool
    {
        $type = $field::class;

        $supportedFields = [
            CraftFields\Checkboxes::class,
            CraftFields\Color::class,
            CraftFields\Date::class,
            CraftFields\Dropdown::class,
            CraftFields\Email::class,
            CraftFields\Lightswitch::class,
            CraftFields\MultiSelect::class,
            CraftFields\Number::class,
            CraftFields\PlainText::class,
            CraftFields\RadioButtons::class,
            CraftFields\Url::class,
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
