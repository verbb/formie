<?php
namespace verbb\formie\options;

class OptionList
{
    // Static Methods
    // =========================================================================

    public static function fromRows(array $rows, ?string $error = null, bool $stale = false): self
    {
        return new self($rows, $error, $stale);
    }

    public static function error(string $message): self
    {
        return new self([], $message);
    }


    // Public Methods
    // =========================================================================

    public function __construct(
        public array $items = [],
        public ?string $error = null,
        public bool $stale = false,
    ) {
    }
}
