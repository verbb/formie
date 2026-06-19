<?php
namespace verbb\formie\records;

use verbb\formie\helpers\Table;

use craft\db\ActiveRecord;

use yii\db\ActiveQueryInterface;

class SubmissionQuizResult extends ActiveRecord
{
    // Static Methods
    // =========================================================================

    public static function tableName(): string
    {
        return Table::FORMIE_SUBMISSION_QUIZ_RESULTS;
    }


    // Public Methods
    // =========================================================================

    public function getSubmission(): ActiveQueryInterface
    {
        return $this->hasOne(Submission::class, ['id' => 'submissionId']);
    }
}
