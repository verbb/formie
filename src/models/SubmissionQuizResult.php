<?php
namespace verbb\formie\models;

use craft\base\Model;

class SubmissionQuizResult extends Model
{
    // Properties
    // =========================================================================
    
    public ?int $id = null;
    public ?int $submissionId = null;
    public float $score = 0;
    public float $maxScore = 0;
    public float $percentage = 0;
    public bool $passed = false;
    public array $questionResults = [];


    // Public Methods
    // =========================================================================

    public function defineRules(): array
    {
        return [
            [['submissionId'], 'required'],
            [['submissionId'], 'integer'],
            [['score', 'maxScore', 'percentage'], 'number'],
            [['passed'], 'boolean'],
            [['questionResults'], 'safe'],
        ];
    }
}
