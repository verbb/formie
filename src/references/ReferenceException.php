<?php
namespace verbb\formie\references;

use RuntimeException;

final class ReferenceException extends RuntimeException
{
    // Public Methods
    // =========================================================================

    public function __construct(public readonly ReferenceDiagnostic $diagnostic)
    {
        // Do not put submitted values, environment names or secret tokens in logs.
        parent::__construct('Reference resolution failed: ' . $diagnostic->value . '.');
    }
}
