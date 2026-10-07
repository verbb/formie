<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\errors\StateConflict;
use verbb\formie\helpers\Table;
use verbb\formie\models\SubmissionProgress as ProgressState;

use Craft;
use craft\db\Query;
use craft\helpers\Json;

use yii\base\Component;

class SubmissionProgress extends Component
{
    // Public Methods
    // =========================================================================

    public function getProgressState(Form $form): ?ProgressState
    {
        // Only a live browser binding resolves progress. Submission authority can
        // survive this optional navigation state, but browser-only bindings cannot.
        $grant = Formie::$plugin->getSubmissionGrants()->bound($form, SubmissionGrants::CONTINUE);

        return $grant ? Formie::$plugin->getSubmissionGrants()->resolveProgress($grant) : null;
    }

    public function loadProgress(int $id): ?ProgressState
    {
        $row = (new Query())->from(Table::FORMIE_SUBMISSION_PROGRESS)->where(['id' => $id])->andWhere(['>', 'expiresAt', time()])->one();

        if (!$row) {
            return null;
        }

        foreach (['id', 'formId', 'siteId', 'submissionId', 'currentPageId', 'version', 'expiresAt'] as $key) {
            $row[$key] = $row[$key] === null ? null : (int)$row[$key];
        }
        $row['content'] = $row['submissionId'] ? [] : (Json::decodeIfJson($row['content']) ?: []);
        return new ProgressState($row);
    }

    public function upsertProgressState(Form $form, Submission $submission, ?int $currentPageId = null): ?ProgressState
    {
        if (!$submission->id) {
            return null;
        }
        $id = (new Query())->select('id')->from(Table::FORMIE_SUBMISSION_PROGRESS)->where(['submissionId' => (int)$submission->id])->scalar();
        $state = $id ? $this->loadProgress((int)$id) : $this->getProgressState($form);

        if ($id && !$state) {
            $this->deleteProgress((int)$id);
        }
        $state ??= $this->_newState($form);
        $state->submissionId = (int)$submission->id;
        $state->currentPageId = $currentPageId;
        $state->content = [];
        $this->saveProgress($state, $submission->stateVersion);
        Formie::$plugin->getSubmissionGrants()->bindProgress($form, $state);
        return $state;
    }

    public function upsertPageState(Form $form, ?int $currentPageId = null, ?int $submissionId = null): ProgressState
    {
        $state = $this->getProgressState($form) ?? $this->_newState($form);
        $state->submissionId = $submissionId ?? $state->submissionId;
        $state->currentPageId = $currentPageId;
        $this->saveProgress($state);
        Formie::$plugin->getSubmissionGrants()->bindProgress($form, $state);
        return $state;
    }

    public function saveProgress(ProgressState $state, ?int $submissionVersion = null): void
    {
        $values = [
            'formId' => $state->formId, 'siteId' => $state->siteId,
            'submissionId' => $state->submissionId, 'browserHash' => $state->browserHash,
            'currentPageId' => $state->currentPageId,
            'content' => $state->submissionId ? null : Json::encode($state->content),
            'version' => $submissionVersion ?? $state->version + 1, 'expiresAt' => $state->expiresAt,
        ];
        $db = Craft::$app->getDb();

        if ($state->id) {
            // Compare-and-swap also protects callers outside the command's resource lock.
            $updated = $db->createCommand()->update(Table::FORMIE_SUBMISSION_PROGRESS, $values, ['id' => $state->id, 'version' => $state->version])->execute();

            if (!$updated) {
                throw new StateConflict($this->loadProgress($state->id)?->version);
            }
        } else {
            $db->createCommand()->insert(Table::FORMIE_SUBMISSION_PROGRESS, $values)->execute();
            $state->id = (int)$db->getLastInsertID();
        }
        $state->version = $values['version'];

        if ($state->submissionId) {
            $state->content = [];
            $db->createCommand()->update(Table::FORMIE_SUBMISSION_GRANTS, ['submissionId' => $state->submissionId], ['progressId' => $state->id])->execute();
        }
    }

    public function clearProgressState(Form $form): void
    {
        Formie::$plugin->getSubmissionGrants()->revokeBrowser($form, SubmissionGrants::CONTINUE);
    }

    public function complete(int $submissionId): void
    {
        Formie::$plugin->getSubmissionGrants()->revokeSubmission($submissionId, SubmissionGrants::CONTINUE);
        Craft::$app->getDb()->createCommand()->delete(Table::FORMIE_SUBMISSION_PROGRESS, ['submissionId' => $submissionId])->execute();
    }

    public function deleteProgress(int $id): void
    {
        $db = Craft::$app->getDb();

        // A browser-only binding has no submission to authorize after its progress
        // disappears. Submission grants remain and lose only their navigation hint.
        $db->createCommand()->delete(Table::FORMIE_SUBMISSION_GRANTS, [
            'progressId' => $id,
            'submissionId' => null,
        ])->execute();
        $db->createCommand()->delete(Table::FORMIE_SUBMISSION_PROGRESS, ['id' => $id])->execute();
    }

    public function pruneProgress(): int
    {
        $db = Craft::$app->getDb();
        $now = time();
        $expiredIds = (new Query())
            ->select('id')
            ->from(Table::FORMIE_SUBMISSION_PROGRESS)
            ->where(['<=', 'expiresAt', $now])
            ->column();

        $db->createCommand()->delete('{{%formie_instance_configs}}', ['<=', 'expiresAt', $now])->execute();

        if ($expiredIds) {
            $db->createCommand()->delete(Table::FORMIE_SUBMISSION_GRANTS, [
                'progressId' => $expiredIds,
                'submissionId' => null,
            ])->execute();
        }

        return $db->createCommand()->delete(Table::FORMIE_SUBMISSION_PROGRESS, ['id' => $expiredIds])->execute();
    }


    // Private Methods
    // =========================================================================

    private function _newState(Form $form): ProgressState
    {
        $days = (int)Formie::$plugin->getSettings()->submissionStateRetentionDays ?: 30;
        return new ProgressState([
            'formId' => (int)$form->id, 'siteId' => (int)$form->siteId,
            'browserHash' => Formie::$plugin->getSubmissionGrants()->browserHash($form),
            'expiresAt' => time() + $days * 86400,
        ]);
    }
}
