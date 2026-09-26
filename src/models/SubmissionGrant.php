<?php
namespace verbb\formie\models;

use craft\base\Model;

class SubmissionGrant extends Model
{
    // Properties
    // =========================================================================

    public ?int $id = null;
    public ?string $token = null;
    public ?int $formId = null;
    public ?int $siteId = null;
    public ?int $submissionId = null;
    public ?int $progressId = null;
    public string $purpose = 'continue-incomplete';
    public ?int $expiresAt = null;
}
