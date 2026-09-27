<?php
namespace verbb\formie\models;

use craft\base\Model;

/** One business run; attempts describe its individual external operations. */
class SubmissionDispatch extends Model
{
    // Properties
    // =========================================================================

    public int $submissionId;
    public string $uid;
    public string $kind;
    public string $status;
    public int $submissionVersion;
    public bool $schedulingComplete = false;
    public ?string $failureCode = null;
    public array $command = [];
}
