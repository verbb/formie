<?php
namespace verbb\formie\state;

use craft\base\Model;
use DateTime;

class DraftSubmissionState extends Model
{
    // Properties
    // =========================================================================

    public ?FormInstanceKey $formInstanceKey = null;
    public ?int $submissionId = null;
    public ?int $currentPageId = null;
    public array $content = [];
    public array $snapshot = [];
    public int $version = 1;
    public ?string $etag = null;
    public ?string $resumeToken = null;
    public ?DateTime $dateUpdated = null;
}
