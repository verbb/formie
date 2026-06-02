<?php
namespace verbb\formie\models;

use craft\base\Model;

class SubmissionExecutionResult extends Model
{
    // Properties
    // =========================================================================

    public ?SubmissionRequest $submissionRequest = null;
    public ?SubmissionResponse $response = null;
}
