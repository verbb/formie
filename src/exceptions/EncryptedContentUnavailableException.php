<?php
namespace verbb\formie\exceptions;

use RuntimeException;

class EncryptedContentUnavailableException extends RuntimeException
{
    // Public Methods
    // =========================================================================

    public function __construct(
        string $message,
        public readonly ?string $keyId = null,
        public readonly mixed $storedValue = null,
    ) {
        parent::__construct($message);
    }
}
