<?php
namespace verbb\formie\references;

/** Collects non-fatal reference failures for previews, queue diagnostics, and support. */
final class ReferenceDiagnostics
{
    // Properties
    // =========================================================================

    private array $_warnings = [];


    // Public Methods
    // =========================================================================

    public function add(string $token, ReferenceDiagnostic $diagnostic): void
    {
        $this->_warnings[] = [
            'token' => $token,
            'diagnostic' => $diagnostic->value,
        ];
    }

    public function all(): array
    {
        return $this->_warnings;
    }

    public function hasWarnings(): bool
    {
        return $this->_warnings !== [];
    }
}
