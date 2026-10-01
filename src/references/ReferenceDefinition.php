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
        public ?array $usages = null,
    ) {
        $this->types = $types ?? match ($valueType->kind) {
            'string' => [ReferenceType::Text],
            'boolean' => [ReferenceType::Boolean],
            'array' => [ReferenceType::List],
            default => throw new InvalidArgumentException('Reference definitions with structured values must declare semantic reference types.'),
        };

        foreach ($this->types as $type) {
            if (!$type instanceof ReferenceType) {
                throw new InvalidArgumentException('Reference definition types must use ReferenceType cases.');
            }
        }

        foreach ($this->usages ?? [] as $usage) {
            if (!$usage instanceof ReferenceUsage) {
                throw new InvalidArgumentException('Reference usages must use ReferenceUsage cases.');
            }
        }
    }

    public function assertAvailable(ReferenceContext $context): void
    {
        $usage = $context->usage ?? ReferenceUsage::forOutput($context->outputContext);

        if (($this->usages !== null && !in_array($usage, $this->usages, true)) || ($this->shape === ReferenceShape::Block && $usage !== ReferenceUsage::RichText)) {
            throw new ReferenceException(ReferenceDiagnostic::ForbiddenSource);
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
            'usages' => $this->usages === null ? null : array_map(static fn(ReferenceUsage $usage): string => $usage->value, $this->usages),
        ];
    }
}
