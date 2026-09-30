<?php
namespace verbb\formie\models;

/** Mutable execution bookkeeping owned by one explicit run, never connection configuration. */
final class IntegrationDeliveryState
{
    // Properties
    // =========================================================================

    public ?IntegrationResult $error = null;
    public bool $skipped = false;
    public bool $writeAccepted = false;
    public bool $uncertain = false;
    public array $outputs = [];
}
