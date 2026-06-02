<?php

declare(strict_types=1);

namespace Tests\Support;

use Craft;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\Table;

final class ResetTestDatabase
{
    public static function resetFormieData(): void
    {
        $db = Craft::$app->getDb();
        $elements = Craft::$app->getElements();

        // Delete submissions first, then forms to respect dependencies.
        foreach (Submission::find()->anyStatus()->all() as $submission) {
            $elements->deleteElement($submission, true);
        }

        foreach (Form::find()->anyStatus()->all() as $form) {
            $elements->deleteElement($form, true);
        }

        // Clear runtime/state tables used by submit-flow and idempotency.
        foreach ([Table::FORMIE_SUBMISSION_WORKFLOW, Table::FORMIE_SUBMISSION_DRAFTS, Table::FORMIE_SUBMISSION_RESUME_TOKENS] as $table) {
            if ($db->tableExists($table)) {
                $db->createCommand()->truncateTable($table)->execute();
            }
        }
    }
}
