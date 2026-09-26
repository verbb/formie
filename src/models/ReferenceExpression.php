<?php
namespace verbb\formie\models;

/** An immutable syntax tree. Parsing never evaluates templates or application objects. */
final readonly class ReferenceExpression
{
    // Public Methods
    // =========================================================================

    public function __construct(
        public string $raw = '',
        public string $target = '',
        public string $identifier = '',
        public string $selector = '',
        public string $default = '',
        public string $transformerId = '',
        public array $transformerParams = [],
        public bool $isValid = false,
        public int $version = 1,
        public ?string $diagnostic = null,
    ) {
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
