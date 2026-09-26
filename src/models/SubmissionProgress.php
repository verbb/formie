<?php
namespace verbb\formie\models;

use craft\base\Model;

class SubmissionProgress extends Model
{
    // Properties
    // =========================================================================

    public ?int $id = null;
    public ?int $formId = null;
    public ?int $siteId = null;
    public ?int $submissionId = null;
    public ?int $currentPageId = null;
    public string $browserHash = '';
    public array $content = [];
    public int $version = 0;
    public ?int $expiresAt = null;
}
