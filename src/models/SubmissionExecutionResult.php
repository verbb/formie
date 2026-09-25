<?php
namespace verbb\formie\models;

use craft\base\Model;

class SubmissionExecutionResult extends Model
{
    // Properties
    // =========================================================================

    public ?SubmissionCommand $command = null;
    public ?SubmissionResponse $response = null;
}
