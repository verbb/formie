<?php
namespace verbb\formie\fields\definitions;

use yii\base\BaseObject;

/**
 * Describes how a field's client payload should model nested children, if any.
 */
class FieldClientChildren extends BaseObject
{
    // Constants
    // =========================================================================

    public const MODEL_SCALAR = 'scalar';
    public const MODEL_FIXED_PARENT = 'fixed-parent';
    public const MODEL_CONTAINER_PARENT = 'container-parent';
    public const MODEL_REPEATABLE_PARENT = 'repeatable-parent';

    public const MODE_PARTS = 'parts';
    public const MODE_ROWS = 'rows';


    // Static Methods
    // =========================================================================

    public static function make(string $model = self::MODEL_SCALAR): self
    {
        return new self(['model' => $model]);
    }


    // Properties
    // =========================================================================

    public string $model = self::MODEL_SCALAR;
    public ?string $mode = null;
    private mixed $_partFieldResolver = null;
    private mixed $_rowResolver = null;

    
    // Public Methods
    // =========================================================================

    public function isScalar(): bool
    {
        return $this->model === self::MODEL_SCALAR;
    }

    public function isFixedParent(): bool
    {
        return $this->model === self::MODEL_FIXED_PARENT;
    }

    public function isContainerParent(): bool
    {
        return $this->model === self::MODEL_CONTAINER_PARENT;
    }

    public function isRepeatableParent(): bool
    {
        return $this->model === self::MODEL_REPEATABLE_PARENT;
    }

    public function withChildren(string $mode): self
    {
        $this->mode = $mode;

        return $this;
    }

    public function withPartFieldResolver(callable $resolver): self
    {
        $this->_partFieldResolver = $resolver;

        return $this;
    }

    public function withRowResolver(callable $resolver): self
    {
        $this->_rowResolver = $resolver;

        return $this;
    }

    public function resolvePartFields(): array
    {
        if (!$this->_partFieldResolver) {
            return [];
        }

        $fields = ($this->_partFieldResolver)();

        return is_array($fields) ? $fields : [];
    }

    public function resolveRows(): array
    {
        if (!$this->_rowResolver) {
            return [];
        }

        $rows = ($this->_rowResolver)();

        return is_array($rows) ? $rows : [];
    }

    public function toArray(): array
    {
        return array_filter([
            'model' => $this->model,
            'mode' => $this->mode,
        ], static fn(mixed $value): bool => $value !== null && $value !== '');
    }
}
