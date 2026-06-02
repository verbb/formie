<?php
namespace verbb\formie\base;

use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyFieldEmailValueEvent;
use verbb\formie\events\ModifyFieldIntegrationValueEvent;
use verbb\formie\events\ModifyFieldValueEvent;
use verbb\formie\fields\coercion\ArrayValueCoercer;
use verbb\formie\fields\coercion\ScalarValueCoercer;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Notification;

use craft\base\ElementInterface;
use craft\helpers\Json;

use Faker\Generator as FakerFactory;

trait FieldValueTrait
{
    // Public Methods
    // =========================================================================

    public function getElementValue(?ElementInterface $element = null): mixed
    {
        if ($element instanceof Submission) {
            $value = $element->getFieldValue($this->valueKey());

            if ($value !== null) {
                return $value;
            }
        }

        return $this->getInitialValue($element);
    }

    public function getValueAsString(mixed $value, ?ElementInterface $element = null): mixed
    {
        $value = $this->defineValueAsString($value, $element);

        $event = new ModifyFieldValueEvent([
            'value' => $value,
            'field' => $this,
            'submission' => $element,
        ]);

        $this->trigger(static::EVENT_MODIFY_VALUE_AS_STRING, $event);

        return $event->value;
    }

    public function getValueAsArray(mixed $value, ?ElementInterface $element = null): mixed
    {
        $value = $this->defineValueAsArray($value, $element);

        $event = new ModifyFieldValueEvent([
            'value' => $value,
            'field' => $this,
            'submission' => $element,
        ]);

        $this->trigger(static::EVENT_MODIFY_VALUE_AS_ARRAY, $event);

        // Deprecated in 4.0.0 - remove at next breakpoint
        // Keep the deprecated JSON event wired to the array projection so older
        // listeners still influence the canonical structured-value path.
        $this->trigger(static::EVENT_MODIFY_VALUE_AS_JSON, $event);

        return $event->value;
    }

    public function getValueForExport(mixed $value, ?ElementInterface $element = null): mixed
    {
        $value = $this->defineValueForExport($value, $element);

        $event = new ModifyFieldValueEvent([
            'value' => $value,
            'field' => $this,
            'submission' => $element,
        ]);

        $this->trigger(static::EVENT_MODIFY_VALUE_FOR_EXPORT, $event);

        return $event->value;
    }

    public function getValueForIntegration(mixed $value, IntegrationField $integrationField, IntegrationInterface $integration, ?ElementInterface $element = null, string $fieldKey = ''): mixed
    {
        $rawValue = $value;
        $value = $this->defineValueForIntegration($value, $integrationField, $integration, $element, $fieldKey);

        $event = new ModifyFieldIntegrationValueEvent([
            'value' => $value,
            'rawValue' => $rawValue,
            'field' => $this,
            'submission' => $element,
            'integrationField' => $integrationField,
            'integration' => $integration,
        ]);

        $this->trigger(static::EVENT_MODIFY_VALUE_FOR_INTEGRATION, $event);

        return $event->value;
    }

    public function getValueForSummary(mixed $value, ?ElementInterface $element = null): mixed
    {
        $value = $this->defineValueForSummary($value, $element);

        $event = new ModifyFieldValueEvent([
            'value' => $value,
            'field' => $this,
            'submission' => $element,
        ]);

        $this->trigger(static::EVENT_MODIFY_VALUE_FOR_SUMMARY, $event);

        return $event->value;
    }

    public function getValueForReference(mixed $value, ?ElementInterface $element = null): mixed
    {
        $value = $this->defineValueForReference($value, $element);

        $event = new ModifyFieldValueEvent([
            'value' => $value,
            'field' => $this,
            'submission' => $element,
        ]);

        $this->trigger(static::EVENT_MODIFY_VALUE_FOR_REFERENCE, $event);

        // Reference output is the field's canonical singular string-like value,
        // so keep existing string-value listeners in the loop during migration.
        $this->trigger(static::EVENT_MODIFY_VALUE_AS_STRING, $event);

        return $event->value;
    }

    public function getValueForReferenceBlock(mixed $value, Notification $notification, ?ElementInterface $element = null): mixed
    {
        $value = $this->defineValueForReferenceBlock($value, $notification, $element);

        $event = new ModifyFieldEmailValueEvent([
            'value' => $value,
            'field' => $this,
            'submission' => $element,
            'notification' => $notification,
        ]);

        $this->trigger(static::EVENT_MODIFY_VALUE_FOR_REFERENCE_BLOCK, $event);

        // Keep the legacy email event available for older listeners, but route
        // it through the deprecation bridge so canonical code stays reference-
        // block first and only logs when the old event is actually in use.
        $this->triggerDeprecatedEmailValueEvent($event);

        return $event->value;
    }

    public function getValueForEmailPreview(FakerFactory $faker): mixed
    {
        $value = $this->defineValueForEmailPreview($faker);

        $event = new ModifyFieldEmailValueEvent([
            'value' => $value,
            'field' => $this,
            'faker' => $faker,
        ]);

        $this->trigger(static::EVENT_MODIFY_VALUE_FOR_EMAIL_PREVIEW, $event);

        return $event->value;
    }

    public function getValueForCondition(mixed $value, Submission $submission): mixed
    {
        return $this->defineValueForCondition($value, $submission);
    }


    // Protected Methods
    // =========================================================================

    protected function defineValueAsString(mixed $value, ElementInterface $element = null): string
    {
        return ScalarValueCoercer::toScalarString($value);
    }

    protected function defineValueAsArray(mixed $value, ElementInterface $element = null): mixed
    {
        $normalizedArrayValue = ArrayValueCoercer::normalizeForField(
            value: $value,
            supportsArray: $this->supportsArrayValue(),
        );

        if ($normalizedArrayValue !== null) {
            return $normalizedArrayValue;
        }

        if ($this->supportsStringValue()) {
            $stringValue = $this->defineValueAsString($value, $element);

            return $stringValue !== '' ? [$stringValue] : [];
        }

        $scalarValue = ScalarValueCoercer::normalizeScalarLike($value);

        if (is_scalar($scalarValue)) {
            return (string)$scalarValue === '' ? [] : [$scalarValue];
        }

        return Json::decode(Json::encode($value));
    }

    protected function defineValueForExport(mixed $value, ElementInterface $element = null): mixed
    {
        // A string-representation will largely suit our needs
        return $this->defineValueAsString($value, $element);
    }

    protected function defineValueForIntegration(mixed $value, IntegrationField $integrationField, IntegrationInterface $integration, ElementInterface $element = null, string $fieldKey = ''): mixed
    {
        $fieldValue = $integrationField->getType() === IntegrationField::TYPE_ARRAY
            ? $this->defineValueAsArray($value, $element)
            : $this->defineValueAsString($value, $element);

        return Integration::convertValueForIntegration($fieldValue, $integrationField);
    }

    protected function defineValueForSummary(mixed $value, ElementInterface $element = null): mixed
    {
        // A string-representation will largely suit our needs
        return $this->defineValueAsString($value, $element);
    }

    protected function defineValueForReference(mixed $value, ElementInterface $element = null): mixed
    {
        return $this->defineValueAsString($value, $element);
    }

    protected function defineValueForReferenceBlock(mixed $value, Notification $notification, ElementInterface $element = null): mixed
    {
        // Keep legacy field overrides working without making the canonical
        // reference-block path depend on the deprecated email-named hook.
        if ($this->_hasLegacyFieldMethodOverride('defineValueForEmail')) {
            return $this->defineValueForEmail($value, $notification, $element);
        }

        return $value;
    }

    protected function defineValueForCondition(mixed $value, Submission $submission): mixed
    {
        // Conditions compare against a stable, comparable shape rather than
        // field-specific field value objects that may be richer but harder to
        // reference consistently from rules and expressions.
        return $this->serializeValue($value, $submission);
    }

    protected function defineValueForEmailPreview(FakerFactory $faker): mixed
    {
        return $faker->text;
    }
}
