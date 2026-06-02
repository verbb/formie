<?php
namespace verbb\formie\state;

use craft\base\Model;

class ResumeToken extends Model
{
    // Properties
    // =========================================================================

    public ?string $token = null;
    public ?int $formId = null;
    public ?int $siteId = null;
    public ?int $submissionId = null;
    public array $capabilities = [];
    public ?int $issuedAt = null;
    public ?int $expiresAt = null;
    public ?int $revokedAt = null;
}
