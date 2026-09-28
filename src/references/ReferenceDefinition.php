<?php
namespace verbb\formie\references;

use verbb\formie\fields\definitions\FieldValueType;

use InvalidArgumentException;

final readonly class ReferenceDefinition
{
    // Properties
    // =========================================================================

    public array $types;


    // Public Methods
    // =========================================================================

    public function __construct(
        public string $id,
        public string $label,
        public string $category,
        public FieldValueType $valueType,
        public array $transforms = [],
        public bool $server = true,
        public bool $browser = false,
        ?array $types = null,
        public ReferenceShape $shape = ReferenceShape::Inline,
        public bool $allowTransforms = true,
    ) {
        $this->types = $types ?? match ($valueType->kind) {
            'string' => [ReferenceType::Text],
            'number' => [ReferenceType::Number],
            'boolean' => [ReferenceType::Boolean],
            'array' => [ReferenceType::List],
            default => throw new InvalidArgumentException('Reference definitions with structured values must declare semantic reference types.'),
        };

        foreach ($this->types as $type) {
            if (!$type instanceof ReferenceType) {
                throw new InvalidArgumentException('Reference definition types must use ReferenceType cases.');
            }
        }
    }

    public function toPickerSource(string $token): array
    {
        return [
            'label' => $this->label,
            'value' => $token,
            'category' => $this->category,
            'valueType' => $this->valueType->toArray(),
            'transforms' => $this->transforms,
            'shape' => $this->shape->value,
            'types' => array_map(static fn(ReferenceType $type): string => $type->value, $this->types),
            'allowTransforms' => $this->allowTransforms,
            'availability' => ['server' => $this->server, 'browser' => $this->browser],
        ];
    }
}
