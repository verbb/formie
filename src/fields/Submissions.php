<?php
namespace verbb\formie\fields;

use verbb\formie\elements\Submission;
use verbb\formie\elements\db\SubmissionQuery;

use Craft;
use craft\fields\BaseRelationField;

class Submissions extends BaseRelationField
{
    // Public Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Submissions (Formie)');
    }

    public static function defaultSelectionLabel(): string
    {
        return Craft::t('formie', 'Add a submission');
    }

    public static function valueType(): string
    {
        return SubmissionQuery::class;
    }

    protected static function elementType(): string
    {
        return Submission::class;
    }
}
