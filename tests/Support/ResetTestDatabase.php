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

        // Payment subscriptions use RESTRICT on submissionId, so clear payment rows first.
        $truncateTables = [
            Table::FORMIE_PAYMENTS,
            Table::FORMIE_SUBSCRIPTIONS,
            Table::FORMIE_SUBMISSION_WORKFLOW,
            Table::FORMIE_SUBMISSION_DRAFTS,
            Table::FORMIE_SUBMISSION_RESUME_TOKENS,
            Table::FORMIE_SUBMISSION_QUIZ_RESULTS,
            Table::FORMIE_PENDING_UPLOADS,
        ];

        if ($db->driverName === 'mysql') {
            $db->createCommand('SET FOREIGN_KEY_CHECKS = 0')->execute();
        } elseif ($db->driverName === 'sqlite') {
            $db->createCommand('PRAGMA foreign_keys = OFF')->execute();
        }

        try {
            foreach ($truncateTables as $table) {
                if ($db->tableExists($table)) {
                    $db->createCommand()->truncateTable($table)->execute();
                }
            }

            // Delete submissions first, then forms to respect dependencies.
            foreach (Submission::find()->anyStatus()->all() as $submission) {
                $elements->deleteElement($submission, true);
            }

            foreach (Form::find()->anyStatus()->all() as $form) {
                $elements->deleteElement($form, true);
            }
        } finally {
            if ($db->driverName === 'mysql') {
                $db->createCommand('SET FOREIGN_KEY_CHECKS = 1')->execute();
            } elseif ($db->driverName === 'sqlite') {
                $db->createCommand('PRAGMA foreign_keys = ON')->execute();
            }
        }
    }
}
