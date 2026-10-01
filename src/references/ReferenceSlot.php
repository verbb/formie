<?php
namespace verbb\formie\references;

/** The persisted slot declares semantics independently of its text. */
final readonly class ReferenceSlot
{
    // Static Methods
    // =========================================================================

    public static function fromStored(mixed $stored): self
    {
        if (is_array($stored) && isset($stored['kind'])) {
            $kind = is_string($stored['kind']) ? ReferenceSlotKind::tryFrom($stored['kind']) : null;

            if (!$kind) {
                throw new ReferenceException(ReferenceDiagnostic::InvalidExpression);
            }
            return new self($kind, $stored['value'] ?? null);
        }

        // Bounded migration for Formie 3/beta mappings. New writes must store kind explicitly.
        if (is_array($stored)) {
            $type = $stored['type'] ?? '';
            $value = $type === 'none' ? '' : ($stored['value'] ?? '');

            if (in_array($type, ['literal', 'custom'], true)) {
                return new self(ReferenceSlotKind::Literal, $value);
            }
            $stored = $value;
        }

        if (is_string($stored) && str_starts_with($stored, '{providerOption:')) {
            $expression = ReferenceParser::parse($stored);
            return new self(ReferenceSlotKind::Literal, $expression->isValid ? $expression->identifier : $stored);
        }
        return new self(is_string($stored) && ReferenceParser::parse($stored)->isValid ? ReferenceSlotKind::Exact : ReferenceSlotKind::Literal, $stored);
    }


    // Public Methods
    // =========================================================================

    public function __construct(public ReferenceSlotKind $kind, public mixed $value)
    {
    }

    public function resolve(ReferenceContext $context): mixed
    {
        return match ($this->kind) {
            ReferenceSlotKind::Literal => $this->value,
            ReferenceSlotKind::Exact => (new ReferenceResolver())->resolveValue((string)$this->value, $context)->requireValue(),
            ReferenceSlotKind::Text => (new ReferenceResolver())->interpolateText((string)$this->value, $context, $context->outputContext),
        };
    }

    public function toArray(): array
    {
        return ['kind' => $this->kind->value, 'value' => $this->value];
    }
}
