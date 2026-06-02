<?php
namespace verbb\formie\models;

use verbb\formie\base\Field;
use verbb\formie\elements\Form;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\Html;

class FieldPath
{
    // Properties
    // =========================================================================

    private array $_handlePath = [];
    private array $_namespacePath = [];
    private string $_fieldHandle = '';


    // Public Methods
    // =========================================================================

    public static function fromField(Field $field): self
    {
        $instance = new self();

        $instance->_fieldHandle = (string)$field->handle;

        $cursor = $field;

        while ($cursor) {
            array_unshift($instance->_handlePath, (string)$cursor->handle);
            array_unshift($instance->_namespacePath, (string)$cursor->getNamespace());

            $parent = $cursor->getParentField();
            $cursor = ($parent instanceof Field) ? $parent : null;
        }

        return $instance;
    }

    // Returns normalized path segments for submission content lookup.
    // Examples: ['singleName'] or ['multiName', 'firstName'] or ['repeater', '0', 'text'].
    public function valueSegments(): array
    {
        $segments = [];

        foreach ($this->_namespacePath as $namespace) {
            if ($namespace === '' || $namespace === 'fields') {
                continue;
            }

            $segments[] = explode('[', str_replace(']', '', $namespace));
        }

        if ($segments) {
            $segments = array_merge(...$segments);
        }

        return ArrayHelper::filterEmpty([...$segments, $this->_fieldHandle]);
    }

    // Returns dot-notation submission content key.
    // Examples: `singleName`, `multiName.firstName`, `repeater.0.text`.
    public function valueKey(): string
    {
        return implode('.', $this->valueSegments());
    }

    // Returns validation error key. Usually identical to valueKey().
    // Example: `multiName.firstName` (some fields may append sub-keys, e.g. `.number`).
    public function errorKey(): string
    {
        return $this->valueKey();
    }

    // Returns field-handle path from root to current field.
    // Example: ['multiName', 'firstName'].
    public function handlePath(): array
    {
        return $this->_handlePath;
    }

    // Returns HTML namespace path from root to current field.
    // Example: ['fields', 'multiName'] or ['fields', 'repeater[0]'].
    public function namespacePath(): array
    {
        return $this->_namespacePath;
    }

    // Returns HTML input name attribute.
    // Examples: `fields[singleName]` or `fields[multiName][firstName]`.
    public function htmlName(?string $extra = null): string
    {
        $names = ArrayHelper::filterEmpty([...$this->_namespacePath, $this->_fieldHandle, $extra]);

        return Html::getInputNameAttribute($names);
    }

    // Returns HTML id attribute.
    // Examples: `fui-contactForm-xpvgyvsp-singleName`, `fui-contactForm-xpvgyvsp-multiName-firstName`.
    public function htmlId(Form $form, ?string $extra = null): string
    {
        $ids = [$form->getRenderId(), ...$this->_namespacePath, $this->_fieldHandle, $extra];

        return Html::getInputIdAttribute(ArrayHelper::filterEmpty($ids));
    }

    // Returns stable data-id style identifier.
    // Examples: `contactForm-singleName`, `contactForm-multiName-firstName`.
    public function htmlDataId(Form $form, ?string $extra = null): string
    {
        $ids = [$form->handle, ...$this->_handlePath, $extra];

        return implode('-', ArrayHelper::filterEmpty($ids));
    }
}
