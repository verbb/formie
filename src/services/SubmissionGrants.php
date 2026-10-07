<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\Table;
use verbb\formie\models\SubmissionGrant;
use verbb\formie\models\SubmissionProgress as ProgressState;

use Craft;
use craft\db\Query;

use yii\base\Component;

use InvalidArgumentException;
use Throwable;

class SubmissionGrants extends Component
{
    // Constants
    // =========================================================================

    public const CONTINUE = 'continue-incomplete';
    public const REVISE = 'revise-complete';


    // Public Methods
    // =========================================================================

    public function browserHash(Form $form): string
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return hash('sha256', 'console:' . getmypid() . '|' . $form->id . '|' . $form->siteId . '|' . $form->getSubmitStateIdentity());
        }
        $session = Craft::$app->getSession();
        $session->open();

        if (!$session->has('formie:authority')) {
            $session->set('formie:authority', Craft::$app->getSecurity()->generateRandomString());
        }
        return hash('sha256', $session->get('formie:authority') . '|' . $form->id . '|' . $form->siteId . '|' . $form->getSubmitStateIdentity());
    }

    public function issue(Submission $submission, string $purpose, ?int $progressId = null, ?int $ttlSeconds = null): SubmissionGrant
    {
        if (!$submission->id || !in_array($purpose, [self::CONTINUE, self::REVISE], true) || ($purpose === self::CONTINUE) !== (bool)$submission->isIncomplete) {
            throw new InvalidArgumentException('The grant purpose does not match the submission.');
        }
        $settings = Formie::$plugin->getSettings();
        $expiresAt = time() + max(1, $ttlSeconds ?? ((int)$settings->saveResumeTokenTtlDays ?: 14) * 86400);

        if ($submission->isIncomplete && $settings->maxIncompleteSubmissionAge > 0) {
            $expiresAt = min($expiresAt, $submission->dateUpdated->getTimestamp() + $settings->maxIncompleteSubmissionAge * 86400);
        }
        $expiresAt = min($expiresAt, $this->_targetDeadline($submission));

        if ($progressId !== null) {
            $progress = Formie::$plugin->getSubmissionProgress()->loadProgress($progressId);

            if (!$progress || $progress->submissionId !== (int)$submission->id || $progress->formId !== (int)$submission->formId || $progress->siteId !== (int)$submission->siteId) {
                throw new InvalidArgumentException('Progress does not match the grant target.');
            }
        }
        $token = Craft::$app->getSecurity()->generateRandomString(64);
        $row = [
            'tokenHash' => $this->hashToken($token), 'bindingHash' => null,
            'progressId' => $progressId, 'submissionId' => (int)$submission->id,
            'formId' => (int)$submission->formId, 'siteId' => (int)$submission->siteId,
            'purpose' => $purpose, 'expiresAt' => $expiresAt,
        ];
        $id = $this->_insert($row);
        return new SubmissionGrant(['id' => $id, 'token' => $token] + array_intersect_key($row, array_flip(['progressId', 'submissionId', 'formId', 'siteId', 'purpose', 'expiresAt'])));
    }

    public function verify(string $token, string $purpose, ?Form $form = null, ?int $submissionId = null): ?SubmissionGrant
    {
        $row = (new Query())->from(Table::FORMIE_SUBMISSION_GRANTS)->where(['tokenHash' => $this->hashToken($token)])->one();

        if (!$row || !hash_equals($row['tokenHash'], $this->hashToken($token)) || !$this->_valid($row, $purpose, $form, $submissionId)) {
            return null;
        }
        return $this->_model($row);
    }

    public function exchange(string $token, string $purpose, Form $form, ?int $submissionId = null): ?SubmissionGrant
    {
        $grant = $this->verify($token, $purpose, $form, $submissionId);

        if (!$grant) {
            return null;
        }
        $bindingHash = $this->browserHash($form);

        if ((new Query())->from(Table::FORMIE_SUBMISSION_GRANTS)->where([
            'bindingHash' => $bindingHash, 'parentId' => $grant->id, 'revokedAt' => null,
        ])->andWhere(['>', 'expiresAt', time()])->exists()) {
            return $grant;
        }
        $this->_insert([
            'bindingHash' => $this->browserHash($form), 'parentId' => $grant->id,
            'progressId' => $grant->progressId, 'submissionId' => $grant->submissionId,
            'formId' => $grant->formId, 'siteId' => $grant->siteId,
            'purpose' => $purpose, 'expiresAt' => $grant->expiresAt,
        ]);
        return $grant;
    }

    public function bindProgress(Form $form, ProgressState $progress): void
    {
        if ($this->bound($form, self::CONTINUE, $progress->submissionId)?->progressId === $progress->id) {
            return;
        }
        $expiresAt = $progress->expiresAt;

        if ($progress->submissionId) {
            $submission = Submission::find()->id($progress->submissionId)->isIncomplete(true)->status(null)->one();

            if (!$submission) {
                return;
            }
            $expiresAt = min($expiresAt, $this->_targetDeadline($submission));
        }
        $this->_insert([
            'bindingHash' => $this->browserHash($form), 'progressId' => $progress->id,
            'submissionId' => $progress->submissionId, 'formId' => $progress->formId,
            'siteId' => $progress->siteId, 'purpose' => self::CONTINUE, 'expiresAt' => $expiresAt,
        ]);
    }

    public function bound(Form $form, string $purpose, ?int $submissionId = null, ?bool $portable = null): ?SubmissionGrant
    {
        $query = (new Query())->from(Table::FORMIE_SUBMISSION_GRANTS)->where([
            'bindingHash' => $this->browserHash($form), 'formId' => (int)$form->id,
            'siteId' => (int)$form->siteId, 'purpose' => $purpose, 'revokedAt' => null,
        ])->andWhere(['>', 'expiresAt', time()]);

        if ($portable !== null) {
            $query->andWhere($portable ? ['not', ['parentId' => null]] : ['parentId' => null]);
        }

        $rows = $query->orderBy(['id' => SORT_DESC])->all();

        foreach ($rows as $row) {
            if ($this->_valid($row, $purpose, $form, $submissionId)) {
                return $this->_model($row);
            }
        }
        return null;
    }

    /** Progress restores navigation only; grant validity never depends on it. */
    public function resolveProgress(SubmissionGrant $grant): ?ProgressState
    {
        if (!$grant->progressId) {
            return null;
        }

        $progress = Formie::$plugin->getSubmissionProgress()->loadProgress($grant->progressId);

        if (!$progress || $progress->formId !== $grant->formId || $progress->siteId !== $grant->siteId || $progress->submissionId !== $grant->submissionId) {
            return null;
        }

        return $progress;
    }

    public function revoke(int $id): void
    {
        Craft::$app->getDb()->createCommand()->update(Table::FORMIE_SUBMISSION_GRANTS, ['revokedAt' => time()], ['id' => $id])->execute();
    }

    public function revokeBrowser(Form $form, string $purpose): void
    {
        Craft::$app->getDb()->createCommand()->update(Table::FORMIE_SUBMISSION_GRANTS, ['revokedAt' => time()], [
            'formId' => (int)$form->id, 'siteId' => (int)$form->siteId,
            'bindingHash' => $this->browserHash($form), 'purpose' => $purpose,
        ])->execute();
    }

    public function revokeSubmission(int $submissionId, ?string $purpose = null): void
    {
        $where = ['submissionId' => $submissionId];

        if ($purpose !== null) {
            $where['purpose'] = $purpose;
        }
        Craft::$app->getDb()->createCommand()->update(Table::FORMIE_SUBMISSION_GRANTS, ['revokedAt' => time()], $where)->execute();
    }

    public function rotate(int $id, Submission $submission, string $purpose, ?int $progressId = null): SubmissionGrant
    {
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $this->revoke($id);
            $grant = $this->issue($submission, $purpose, $progressId);
            $transaction->commit();
            return $grant;
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public function hashToken(string $token): string
    {
        return 'v1:' . hash('sha256', $token);
    }

    public function prune(): int
    {
        return Craft::$app->getDb()->createCommand()->delete(Table::FORMIE_SUBMISSION_GRANTS, ['<=', 'expiresAt', time()])->execute();
    }


    // Private Methods
    // =========================================================================

    private function _targetDeadline(Submission $submission): int
    {
        $deadline = PHP_INT_MAX;
        $days = (int)Formie::$plugin->getSettings()->maxIncompleteSubmissionAge;

        if ($submission->isIncomplete && $days > 0) {
            $deadline = $submission->dateUpdated->getTimestamp() + $days * 86400;
        }
        $form = $submission->getForm();

        if ((int)$form->dataRetentionValue > 0 && in_array($form->dataRetention, ['minutes', 'hours', 'days', 'weeks', 'months', 'years'], true)) {
            $retentionEnd = clone $submission->dateCreated;
            $retentionEnd->modify('+' . (int)$form->dataRetentionValue . ' ' . $form->dataRetention);
            $deadline = min($deadline, $retentionEnd->getTimestamp());
        }
        return $deadline;
    }

    private function _insert(array $row): int
    {
        Craft::$app->getDb()->createCommand()->insert(Table::FORMIE_SUBMISSION_GRANTS, $row + ['dateCreated' => gmdate('Y-m-d H:i:s')])->execute();
        return (int)Craft::$app->getDb()->getLastInsertID();
    }

    private function _valid(array $row, string $purpose, ?Form $form, ?int $submissionId): bool
    {
        if ($row['purpose'] !== $purpose || $row['revokedAt'] !== null || (int)$row['expiresAt'] <= time()
            || ($form && ((int)$row['formId'] !== (int)$form->id || (int)$row['siteId'] !== (int)$form->siteId))
            || ($submissionId !== null && (int)$row['submissionId'] !== $submissionId)) {
            return false;
        }

        if ($row['parentId']) {
            $parent = (new Query())->from(Table::FORMIE_SUBMISSION_GRANTS)->where(['id' => $row['parentId']])->one();

            if (!$parent || !$this->_valid($parent, $purpose, $form, $submissionId)) {
                return false;
            }
        }

        if (!$row['submissionId']) {
            // Browser-only bindings have no durable submission authority. Their
            // progress row is their target, so they end when that row disappears.
            return $purpose === self::CONTINUE && $this->resolveProgress($this->_model($row)) !== null;
        }
        $submission = Submission::find()->id((int)$row['submissionId'])->siteId((int)$row['siteId'])->isIncomplete(null)->isSpam(null)->status(null)->one();

        if ($submission && $submission->isIncomplete) {
            $days = (int)Formie::$plugin->getSettings()->maxIncompleteSubmissionAge;

            if ($days > 0 && $submission->dateUpdated->getTimestamp() + $days * 86400 <= time()) {
                return false;
            }
        }
        return $submission && $this->_targetDeadline($submission) > time() && (int)$submission->formId === (int)$row['formId'] && ($purpose === self::CONTINUE) === (bool)$submission->isIncomplete;
    }

    private function _model(array $row): SubmissionGrant
    {
        $data = array_intersect_key($row, array_flip(['id', 'formId', 'siteId', 'submissionId', 'progressId', 'purpose', 'expiresAt']));

        foreach (['id', 'formId', 'siteId', 'submissionId', 'progressId', 'expiresAt'] as $key) {
            $data[$key] = $data[$key] === null ? null : (int)$data[$key];
        }
        return new SubmissionGrant($data);
    }
}
