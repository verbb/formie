<?php
namespace verbb\formie\base;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyElementFieldsEvent;
use verbb\formie\events\ModifyFieldIntegrationValueEvent;
use verbb\formie\fields\MultiLineText;
use verbb\formie\fields\SingleLineText;
use verbb\formie\fields\Table;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface as CraftFieldInterface;
use craft\fields;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;

use yii\base\Event;
use yii\helpers\Markdown;

use DateTime;
use DateTimeZone;

abstract class Element extends Integration
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_ELEMENT_FIELDS = 'modifyElementFields';


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


    // Properties
    // =========================================================================

    public ?array $attributeMapping = null;
    public ?array $fieldMapping = null;
    public bool $updateElement = false;
    public ?array $updateElementMapping = null;
    public bool $updateSearchIndexes = true;
    public bool $overwriteValues = false;


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        Event::on(self::class, self::EVENT_MODIFY_FIELD_MAPPING_VALUE, function(ModifyFieldIntegrationValueEvent $event) {
            // For rich-text enabled fields, retain the HTML (safely)
            if ($event->field instanceof MultiLineText || $event->field instanceof SingleLineText) {
                $event->value = StringHelper::htmlDecode($event->value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
            }

            // For Date fields as a destination, convert to UTC from system time
            if ($event->integrationField->getType() === IntegrationField::TYPE_DATECLASS) {
                if ($event->value instanceof DateTime) {
                    $timezone = new DateTimeZone(Craft::$app->getTimeZone());

                    $event->value = DateTime::createFromFormat('Y-m-d H:i:s', $event->value->format('Y-m-d H:i:s'), $timezone);
                }
            }

            // Element fields should map 1-for-1
            if ($event->field instanceof ElementFieldInterface) {
                $event->value = $event->submission->getFieldValue($event->field->handle)->ids();
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
        });
    }

    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('formie/settings/elements/edit/' . $this->id);
    }

    public function getIconUrl(): string
    {
        $handle = $this->getClassHandle();

        return Craft::$app->getAssetManager()->getPublishedUrl("@verbb/formie/web/assets/cp/dist/img/elements/{$handle}.svg", true);
    }

    public function getSettingsHtml(): ?string
    {
        $handle = $this->getClassHandle();

        return Craft::$app->getView()->renderTemplate("formie/integrations/elements/{$handle}/_plugin-settings", [
            'integration' => $this,
        ]);
    }

    public function getFormSettingsHtml(Form $form): string
    {
        $handle = $this->getClassHandle();

        return Craft::$app->getView()->renderTemplate("formie/integrations/elements/{$handle}/_form-settings", [
            'integration' => $this,
            'form' => $form,
        ]);
    }

    public function getFormSettings(bool $useCache = true): IntegrationFormSettings|bool
    {
        // Always fetch, no real need for cache
        return $this->fetchFormSettings();
    }

    public static function convertValueForIntegration(mixed $value, IntegrationField $integrationField): mixed
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


    // Protected Methods
    // =========================================================================

    protected function getFieldTypeForField(string $fieldClass): string
    {
        // Provide a map of all native Craft fields to the data we expect
        $fieldTypeMap = [
            fields\Assets::class => IntegrationField::TYPE_ARRAY,
            fields\Categories::class => IntegrationField::TYPE_ARRAY,
            fields\Checkboxes::class => IntegrationField::TYPE_ARRAY,
            fields\Date::class => IntegrationField::TYPE_DATECLASS,
            fields\Entries::class => IntegrationField::TYPE_ARRAY,
            fields\Lightswitch::class => IntegrationField::TYPE_BOOLEAN,
            fields\MultiSelect::class => IntegrationField::TYPE_ARRAY,
            fields\Number::class => IntegrationField::TYPE_FLOAT,
            fields\Table::class => IntegrationField::TYPE_ARRAY,
            fields\Tags::class => IntegrationField::TYPE_ARRAY,
            fields\Users::class => IntegrationField::TYPE_ARRAY,
        ];

        if (is_a($fieldClass, fields\BaseRelationField::class, true) || is_subclass_of($fieldClass, fields\BaseRelationField::class, true)) {
            return IntegrationField::TYPE_ARRAY;
        }

        return $fieldTypeMap[$fieldClass] ?? IntegrationField::TYPE_STRING;
    }

    protected function fieldCanBeUniqueId(CraftFieldInterface $field): bool
    {
        $type = $field::class;

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

    protected function getElementForPayload(string $elementType, string $identifier, Submission $submission): ElementInterface
    {
        $element = new $elementType();

        // Pick from the available update attributes, depending on the identifier picked (e.g. `entryTypeId`, etc).
        $updateAttributes = $this->getUpdateAttributes()[$identifier] ?? [];

        // Check if configuring update, and find an existing element, depending on mapping
        $updateElementValues = $this->getFieldMappingValues($submission, $this->updateElementMapping, $updateAttributes);
        $updateElementValues = array_filter($updateElementValues);

        if ($updateElementValues) {
            $query = $elementType::find($updateElementValues);

            // Fina elements of any status, like disabled
            $query->status(null);

            Craft::configure($query, $updateElementValues);

            if ($foundElement = $query->one()) {
                $element = $foundElement;
            }
        }

        return $element;
    }
}
