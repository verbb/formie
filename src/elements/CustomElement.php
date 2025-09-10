<?php
namespace verbb\formie\elements;

use verbb\formie\base\FieldInterface;
use verbb\formie\helpers\ArrayHelper;

use Craft;
use craft\base\Element;
use craft\base\PreviewableFieldInterface;
use craft\errors\InvalidFieldException;
use craft\helpers\Json;
use craft\web\UploadedFile;

use yii\base\InvalidCallException;
use yii\base\UnknownPropertyException;

use UnitEnum;

// This class handles the overriding of custom field values compared to normal element classes that rely on field layouts.
// Because we roll our own field instances without field layouts or associated with `craft\base\FieldInterface` there's some
// overriding to do for getting fields and their values. Anything Submission-specific should stay in the Submission element class.
class CustomElement extends Element
{
    // Properties
    // =========================================================================

    private array $_fieldValues = [];
    private array $_normalizedFieldValues = [];


    // Public Methods
    // =========================================================================

    public function __isset($name): bool
    {
        // Is this the "field:handle" syntax?
        if (strncmp($name, 'field:', 6) === 0) {
            return $this->getFieldByHandle(substr($name, 6)) !== null;
        }

        return parent::__isset($name) || $this->getFieldByHandle($name);
    }

    public function __get($name)
    {
        // Is this the "field:handle" syntax?
        if (strncmp($name, 'field:', 6) === 0) {
            return $this->getFieldValue(substr($name, 6));
        }

        // If this is a field, make sure the value has been normalized before returning the CustomFieldBehavior value
        if ($this->getFieldByHandle($name) !== null) {
            return $this->_clonedFieldValue($name);
        }

        return parent::__get($name);
    }

    public function __set($name, $value)
    {
        // Is this the "field:handle" syntax?
        if (strncmp($name, 'field:', 6) === 0) {
            $this->setFieldValue(substr($name, 6), $value);
            return;
        }

        try {
            parent::__set($name, $value);
        } catch (InvalidCallException|UnknownPropertyException $e) {
            // Is this is a field?
            if ($this->getFieldByHandle($name) !== null) {
                $this->setFieldValue($name, $value);
            } else {
                throw $e;
            }
        }
    }

    public function getFieldByHandle(string $handle): ?FieldInterface
    {
        return ArrayHelper::firstWhere($this->getFields(), 'handle', $handle);
    }

    public function getFieldById(int $id): ?FieldInterface
    {
        return ArrayHelper::firstWhere($this->getFields(), 'id', $id);
    }

    public function getFieldBySearchIndex(string $attribute): ?FieldInterface
    {
        foreach ($this->getFields() as $field) {
            if ("field:$field->id" === $attribute) {
                return $field;
            }
        }

        return null;
    }

    public function setFieldContent(null|string|array $content): void
    {
        if (is_string($content)) {
            if (Json::isJsonObject($content)) {
                $content = Json::decode($content);
            }
        }

        if ($content) {
            // Content will be keyed by the field UID, so swap back to the handle
            $preppedContent = [];

            foreach ($this->getFields() as $field) {
                if ($field::dbType() !== null && isset($content[$field->uid])) {
                    $preppedContent[$field->handle] = $content[$field->uid];
                }
            }

            $this->setFieldValues($preppedContent);
        }
    }

    public function setFieldValuesFromRequest(string $paramNamespace = ''): void
    {
        if ($paramNamespace) {
            $values = Craft::$app->getRequest()->getBodyParam($paramNamespace, []);
        } else {
            $values = Craft::$app->getRequest()->getBodyParams();
        }

        foreach ($this->fieldLayoutFields(true) as $field) {
            // Do we have any post data for this field?
            if (isset($values[$field->handle])) {
                $value = $values[$field->handle];
            } else if ($paramNamespace && UploadedFile::getInstancesByName("$paramNamespace.$field->handle")) {
                // A file was uploaded for this field
                $value = null;
            } else {
                continue;
            }

            $this->setFieldValueFromRequest($field->handle, $value);
        }
    }

    public function setFieldValueFromRequest(string $fieldHandle, mixed $value): void
    {
        $field = $this->getFieldByHandle($fieldHandle);

        if (!$field) {
            return;
        }

        $value = $field->normalizeValueFromRequest($value, $this);
        $this->setFieldValue($field->handle, $value);
        $this->_normalizedFieldValues[$field->handle] = true;
    }

    public function getFieldValue(string $fieldHandle): mixed
    {
        // Make sure the value has been normalized
        $this->normalizeFieldValue($fieldHandle);

        return $this->_fieldValues[$fieldHandle] ?? null;
    }

    public function setFieldValue(string $fieldHandle, mixed $value): void
    {
        // Support dot-notation for setting content
        ArrayHelper::setValue($this->_fieldValues, $fieldHandle, $value);

        // Don't assume that $value has been normalized
        unset($this->_normalizedFieldValues[$fieldHandle]);        
    }

    public function serializeFieldValues(): array
    {
        // When saving content, ensure that it's saved with UIDs, not handles
        $content = [];

        foreach ($this->getFields() as $field) {
            if ($field::dbType() !== null) {
                $serializedValue = $field->serializeValue($this->getFieldValue($field->handle), $this);
                
                $content[$field->uid] = $serializedValue;
            }
        }

        // Recursively filter any null values
        $content = ArrayHelper::filterNull($content);

        // Cleanup any empty arrays as a result
        $content = ArrayHelper::recursiveFilter($content, function($value): bool {
            return $value !== [];
        });

        return $content;
    }


    // Protected Methods
    // =========================================================================

    protected function fieldLayoutFields(bool $visibleOnly = false, bool $editableOnly = false): array
    {
        return $this->getFields();
    }

    protected function normalizeFieldValue(string $fieldHandle): void
    {
        // Have we already normalized this value?
        if (isset($this->_normalizedFieldValues[$fieldHandle])) {
            return;
        }

        $field = $this->getFieldByHandle($fieldHandle);

        if (!$field) {
            return;
        }

        $value = $this->_fieldValues[$fieldHandle] ?? null;

        // Support dot-notation for setting content
        ArrayHelper::setValue($this->_fieldValues, $fieldHandle, $field->normalizeValue($value, $this));

        $this->_normalizedFieldValues[$fieldHandle] = true;
    }

    protected function attributeHtml(string $attribute): string
    {
        // Is this a custom field?
        if (preg_match('/^field:(.+)/', $attribute, $matches)) {
            $field = $this->getFieldByhandle($matches[1]);

            if ($field instanceof PreviewableFieldInterface) {
                $value = $this->getFieldValue($field->handle);

                return $field->getPreviewHtml($value, $this);
            }

            return '';
        }

        return parent::attributeHtml($attribute);
    }
    

    // Private Methods
    // =========================================================================

    private function _clonedFieldValue(string $fieldHandle): mixed
    {
        $value = $this->getFieldValue($fieldHandle);

        if (is_object($value) && !$value instanceof UnitEnum) {
            return clone $value;
        }

        return $value;
    }

}
