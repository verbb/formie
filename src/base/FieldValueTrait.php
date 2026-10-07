<?php
namespace verbb\formie\base;

use verbb\formie\content\FieldStorageCodec;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyFieldEmailValueEvent;
use verbb\formie\events\ModifyFieldIntegrationValueEvent;
use verbb\formie\events\ModifyFieldValueEvent;
use verbb\formie\fields\coercion\ScalarValueCoercer;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Notification;

use Craft;
use craft\base\ElementInterface;

use UnitEnum;

use Faker\Generator as FakerFactory;

trait FieldValueTrait
{
    // Public Methods
    // =========================================================================

    public function getElementValue(?ElementInterface $element = null): mixed
    {
        if ($element instanceof Submission) {
            $element->getContentManager();
            $state = $element->getContentState();
            $present = array_key_exists($this->uid, $state->rawValuesByUid) || array_key_exists($this->uid, $state->normalizedValuesByUid);
            $value = $element->getFieldValue($this->valueKey());

            if ($value !== null || $present) {
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

    public function getValueAsData(mixed $value, ?ElementInterface $element = null): mixed
    {
        if (!$this->_projectingLegacyData && $this->_hasLegacyFieldMethodOverride('getValueAsJson')) {
            Craft::$app->getDeprecator()->log(static::class . '::getValueAsJson', 'Implement defineValueAsData() instead of overriding getValueAsJson().');
            $this->_projectingLegacyData = true;

            try {
                $value = $this->getValueAsJson($value, $element);
            } finally {
                $this->_projectingLegacyData = false;
            }
        } else {
            $value = $this->defineValueAsData($value, $element);

            if ($this->_projectingLegacyData) {
                return $value;
            }
        }

        $event = new ModifyFieldValueEvent([
            'value' => $value,
            'field' => $this,
            'submission' => $element,
        ]);

        $this->trigger(static::EVENT_MODIFY_VALUE_AS_DATA, $event);


        return FieldStorageCodec::assertSafe($event->value);
    }

    public function serializeValueForClientInput(mixed $value, ?ElementInterface $element = null): mixed
    {
        return FieldStorageCodec::assertSafe($value);
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
        $rawValue = $this->_copyProjectionValue($value);
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

    protected function defineValueAsData(mixed $value, ElementInterface $element = null): mixed
    {
        if ($this->_hasLegacyFieldMethodOverride('defineValueAsJson')) {
            Craft::$app->getDeprecator()->log(static::class . '::defineValueAsJson', 'Implement defineValueAsData() instead of defineValueAsJson().');
            return $this->defineValueAsJson($value, $element);
        }

        return FieldStorageCodec::assertSafe($value);
    }

    protected function defineValueForExport(mixed $value, ElementInterface $element = null): mixed
    {
        // A string-representation will largely suit our needs
        return $this->defineValueAsString($value, $element);
    }

    protected function defineValueForIntegration(mixed $value, IntegrationField $integrationField, IntegrationInterface $integration, ElementInterface $element = null, string $fieldKey = ''): mixed
    {
        $fieldValue = $integrationField->getType() === IntegrationField::TYPE_ARRAY
            ? $this->defineValueAsData($value, $element)
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

        return $this->_copyProjectionValue($value);
    }

    protected function defineValueForCondition(mixed $value, Submission $submission): mixed
    {
        return $this->defineValueAsData($value, $submission);
    }

    protected function defineValueForEmailPreview(FakerFactory $faker): mixed
    {
        return $faker->text;
    }


    // Private Methods
    // =========================================================================

    private function _copyProjectionValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn($item) => $this->_copyProjectionValue($item), $value);
        }

        return is_object($value) && !$value instanceof UnitEnum ? clone $value : $value;
    }
}
