<?php
namespace verbb\formie\content;

final readonly class ContentEncryptionKey
{
    // Public Methods
    // =========================================================================

    public function __construct(
        public string $value,
        public string $bytes,
        public string $id,
    ) {
    }
}
