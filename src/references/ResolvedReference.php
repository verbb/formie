<?php
namespace verbb\formie\references;

use verbb\formie\base\FieldInterface;
use verbb\formie\models\ReferenceExpression;

final readonly class ResolvedReference
{
    // Public Methods
    // =========================================================================

    public function __construct(
        public ReferenceExpression $expression,
        public mixed $value = null,
        public ?ReferenceDefinition $definition = null,
        public ?ReferenceDiagnostic $diagnostic = null,
        public ?FieldInterface $field = null,
        public string $fieldProjection = 'value',
    ) {
    }

    public function requireValue(): mixed
    {
        if ($this->diagnostic !== null) {
            throw new ReferenceException($this->diagnostic);
        }
        return $this->value;
    }
}
