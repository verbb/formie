<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\Table;

use craft\db\Migration;

class m261104_080000_trashed_submission_files extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->tableExists(Table::FORMIE_TRASHED_SUBMISSION_FILES)) {
            $this->createTable(Table::FORMIE_TRASHED_SUBMISSION_FILES, [
                'id' => $this->primaryKey(),
                'submissionId' => $this->integer()->notNull(),
                'uploads' => $this->text(),
                'chosenAssetIds' => $this->text(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(null, Table::FORMIE_TRASHED_SUBMISSION_FILES, 'submissionId', true);
        }

        $this->rememberTrashedSubmissions();

        return true;
    }

    public function safeDown(): bool
    {
        echo "m261104_080000_trashed_submission_files cannot be reverted.\n";

        return false;
    }

    public function rememberTrashedSubmissions(): void
    {
        // Submissions already in the trash would otherwise leave their files behind when they're purged.
        foreach (Form::find()->site('*')->unique()->status(null)->trashed(null)->all() as $form) {
            if ($form->fileUploadsAction !== 'delete') {
                continue;
            }

            $query = Submission::find()
                ->withoutCpIndexScope()
                ->formId($form->id)
                ->site('*')
                ->unique()
                ->isIncomplete(null)
                ->isSpam(null)
                ->status(null)
                ->trashed(true);

            foreach ($query->each() as $submission) {
                $submission->setForm($form);

                Formie::$plugin->getFileUploads()->rememberTrashedSubmissionFiles($submission);
            }
        }
    }
}
