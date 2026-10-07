<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\enums\SubmissionAuthorityType;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\enums\SubmissionUploadStatus;
use verbb\formie\fields\FileUpload;
use verbb\formie\helpers\DataRetentionHelper;
use verbb\formie\helpers\FileUploadRetentionHelper;
use verbb\formie\helpers\References;
use verbb\formie\helpers\Table;
use verbb\formie\helpers\UploadAccess;
use verbb\formie\helpers\UploadLimits;
use verbb\formie\models\SubmissionCommand;
use verbb\formie\models\SubmissionUploadClaim;
use verbb\formie\records\Submission as SubmissionRecord;

use Craft;
use craft\db\Query;
use craft\elements\Asset;
use craft\elements\db\AssetQuery;
use craft\helpers\Assets;
use craft\helpers\Console;
use craft\helpers\Db;
use craft\models\VolumeFolder;

use yii\base\Component;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\TooManyRequestsHttpException;

use DateTime;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class FileUploads extends Component
{
    // Properties
    // =========================================================================

    private array $_lockedUploads = [];


    // Public Methods
    // =========================================================================

    public function getStagingFolder(): VolumeFolder
    {
        $assets = Craft::$app->getAssets();

        if ($assets->getTempAssetUploadFs()->getRootUrl() !== null) {
            throw new RuntimeException('Formie uploads require a private temporary asset filesystem without public URLs.');
        }

        return $assets->getUserTemporaryUploadFolder();
    }

    public function saveStagedAsset(Asset $asset, Form $form, ?int $submissionId, string $fieldUid, string $contentKey): bool
    {
        $bytes = $asset->tempFilePath && is_file($asset->tempFilePath) ? filesize($asset->tempFilePath) : false;

        if ($bytes === false || $bytes > UploadLimits::maxFileBytes()) {
            throw new BadRequestHttpException('Uploaded file exceeds the maximum allowed size or is unavailable.');
        }

        $browserHash = Formie::$plugin->getSubmissionGrants()->browserHash($form);
        $mutex = Craft::$app->getMutex();
        $key = 'formie.upload-budget.' . $browserHash;

        if (!$mutex->acquire($key, 5)) {
            throw new TooManyRequestsHttpException('Another upload is being saved. Please retry.');
        }

        try {
            // Count abandoned/expired files until physical cleanup, not only live capabilities.
            $usage = (new Query())->select([
                'files' => 'COUNT(*)', 'bytes' => 'COALESCE(SUM([[assets.size]]), 0)',
            ])->from(['uploads' => Table::FORMIE_PENDING_UPLOADS])
                ->innerJoin(['assets' => '{{%assets}}'], '[[assets.id]] = [[uploads.assetId]]')
                ->where(['uploads.formId' => (int)$form->id, 'uploads.siteId' => (int)$form->siteId, 'uploads.browserHash' => $browserHash])
                ->andWhere(['not', ['uploads.state' => SubmissionUploadStatus::FINALIZED->value]])->one();
            $settings = Formie::$plugin->getSettings();

            if ((int)$usage['files'] + 1 > max(1, $settings->maxStagedUploadFiles)
                || (int)$usage['bytes'] + $bytes > max(1, $settings->maxStagedUploadBytes)) {
                throw new BadRequestHttpException('The staged upload budget has been reached. Remove unused files before uploading more.');
            }

            if (!Craft::$app->getElements()->saveElement($asset)) {
                return false;
            }
            $this->trackSubmissionAsset($asset, (int)$form->id, $submissionId, $fieldUid, $form, $contentKey);

            return true;
        } finally {
            $mutex->release($key);
        }
    }

    public function trackSubmissionAsset(Asset $asset, int $formId, ?int $submissionId, ?string $fieldUid = null, ?Form $form = null, ?string $contentKey = null): void
    {
        $form ??= Form::find()->id($formId)->site('*')->status(null)->one();

        if (!$form) {
            throw new InvalidArgumentException('Upload form is unavailable.');
        }
        $now = gmdate('Y-m-d H:i:s');
        $existing = (new Query())
            ->select(['id'])
            ->from(Table::FORMIE_PENDING_UPLOADS)
            ->where(['assetId' => (int)$asset->id])
            ->scalar();

        $progress = Formie::$plugin->getSubmissionProgress()->getProgressState($form);
        $values = [
            'assetId' => (int)$asset->id,
            'formId' => $formId > 0 ? $formId : null,
            'submissionId' => $submissionId > 0 ? $submissionId : null,
            'fieldUid' => $fieldUid,
            'isFinalized' => false,
            'state' => SubmissionUploadStatus::STAGED->value,
            'siteId' => (int)$form->siteId,
            'browserHash' => Formie::$plugin->getSubmissionGrants()->browserHash($form),
            'contentKey' => $contentKey,
            'progressId' => $progress?->id,
            'expiresAt' => $this->_uploadExpiry(),
            'dateUpdated' => $now,
        ];

        if ($existing) {
            // Tracking must never transfer ownership or reopen a finalized upload.
            return;
        }
        $values['dateCreated'] = $now;
        $values['uid'] = Craft::$app->getSecurity()->generateRandomString(36);

        Craft::$app->getDb()->createCommand()->insert(Table::FORMIE_PENDING_UPLOADS, $values)->execute();
    }

    /**
     * Resolve submitted attachment capabilities into ordinary asset IDs while preserving
     * request-local authorization evidence for the later persistence boundary.
     */
    public function authorizeSubmissionUploadReferences(Submission $submission, FileUpload $field, string $contentKey, array $references): array
    {
        $form = $submission->getForm();
        $claims = $submission->getContentState()->uploadClaims;

        if (!$form || !$claims) {
            throw new ForbiddenHttpException('Upload authorization context is unavailable.');
        }

        $assetIds = [];

        foreach ($references as $reference) {
            $uploadUid = is_array($reference) ? trim((string)($reference['uploadUid'] ?? '')) : '';
            $attachToken = is_array($reference) ? trim((string)($reference['attachToken'] ?? '')) : '';
            $upload = UploadAccess::resolveToken($attachToken, 'attach');

            if ($uploadUid === '' || $attachToken === '' || !$upload || !hash_equals((string)$upload['uid'], $uploadUid)) {
                throw new ForbiddenHttpException('Invalid upload attachment capability.');
            }

            $this->_assertAttachableUpload($upload, $submission, $field, $contentKey);
            $claim = $this->_claimFromRow($upload);
            $claims->add($claim);
            $assetIds[] = $claim->assetId;
        }

        return $assetIds;
    }

    public function trackFromFieldAsset(Asset $asset, FileUpload $field, mixed $element): void
    {
        if (!method_exists($element, 'getForm')) {
            return;
        }

        $form = $element->getForm();

        if (!$form) {
            return;
        }

        $submissionId = property_exists($element, 'id') && $element->id ? (int)$element->id : null;
        $this->trackSubmissionAsset($asset, (int)$form->id, $submissionId, $field->uid, $form, $field->valueKey());
    }

    public function finalizeSubmissionUploads(int $submissionId): void
    {
        if ($submissionId <= 0) {
            return;
        }

        $submission = Submission::find()->id($submissionId)->isIncomplete(null)->isSpam(null)->status(null)->one();

        if (!$submission || $submission->isIncomplete) {
            return;
        }
        $accepted = [];

        foreach ($submission->getFieldValuesForField(FileUpload::class) as $value) {
            $accepted = array_merge($accepted, $this->_extractAssetIds($value));
        }

        if ((new Query())->from(Table::FORMIE_PENDING_UPLOADS)->where([
            'submissionId' => $submissionId, 'assetId' => $accepted, 'state' => SubmissionUploadStatus::BOUND->value,
        ])->andWhere(['or', ['promotionState' => null], ['not', ['promotionState' => 'moved']]])->exists()) {
            throw new RuntimeException('Accepted uploads must finish promotion before finalization.');
        }
        Craft::$app->getDb()->createCommand()->update(Table::FORMIE_PENDING_UPLOADS, [
            'state' => SubmissionUploadStatus::FINALIZED->value, 'isFinalized' => true, 'dateUpdated' => gmdate('Y-m-d H:i:s'),
        ], ['submissionId' => $submissionId, 'assetId' => $accepted, 'state' => SubmissionUploadStatus::BOUND->value, 'promotionState' => 'moved'])->execute();
    }

    public function hasAcceptedUploads(Submission $submission): bool
    {
        foreach ($submission->getFieldValuesForField(FileUpload::class) as $value) {
            if ($this->_extractAssetIds($value)) {
                return true;
            }
        }

        return false;
    }

    public function removeUploadByAssetId(int $assetId, ?int $formId = null, ?string $fieldUid = null): bool
    {
        if ($assetId <= 0) {
            return false;
        }

        $mutex = Craft::$app->getMutex();
        $key = 'formie.upload.' . $assetId;

        if (isset($this->_lockedUploads[$assetId]) || !$mutex->acquire($key, 0)) {
            return false;
        }

        try {
            $upload = $this->getTrackedUploadByAssetId($assetId, $formId, $fieldUid);

            if (!$upload || $upload['state'] !== SubmissionUploadStatus::STAGED->value || $this->isReferenced($assetId)) {
                return false;
            }

            if (Craft::$app->getDb()->createCommand()->update(Table::FORMIE_PENDING_UPLOADS, ['state' => SubmissionUploadStatus::REJECTED->value, 'expiresAt' => time()], ['id' => $upload['id'], 'state' => SubmissionUploadStatus::STAGED->value])->execute() !== 1) {
                return false;
            }

            return $this->_deleteTrackedAsset($upload);
        } finally {
            $mutex->release($key);
        }
    }

    public function getUploadMetadata(array $assetIds, ?int $formId = null, ?string $fieldUid = null): array
    {
        if (!$assetIds) {
            return [];
        }

        $query = (new Query())
            ->select('*')
            ->from(Table::FORMIE_PENDING_UPLOADS)
            ->where(['assetId' => array_map('intval', $assetIds)]);

        if ($formId !== null && $formId > 0) {
            $query->andWhere(['formId' => $formId]);
        }

        if (is_string($fieldUid) && trim($fieldUid) !== '') {
            $query->andWhere(['fieldUid' => trim($fieldUid)]);
        }

        return $query->all();
    }

    public function purgeStalePendingUploads(?int $olderThanTimestamp = null): int
    {
        $promotion = ['or', ['promotionState' => null], ['submissionId' => null, 'promotionState' => 'moving']];
        $expiry = ['<=', 'expiresAt', time()];

        if ($olderThanTimestamp !== null) {
            $expiry = ['or', $expiry, ['<', 'dateUpdated', gmdate('Y-m-d H:i:s', $olderThanTimestamp)]];
        }
        $rows = (new Query())
            ->select('*')
            ->from(Table::FORMIE_PENDING_UPLOADS)
            ->where(['state' => [SubmissionUploadStatus::STAGED->value, SubmissionUploadStatus::BOUND->value, SubmissionUploadStatus::EXPIRED->value, SubmissionUploadStatus::REJECTED->value]])
            ->andWhere($promotion)
            ->andWhere($expiry)
            ->all();

        $count = 0;

        foreach ($rows as $row) {
            $assetId = (int)($row['assetId'] ?? 0);

            $mutex = Craft::$app->getMutex();
            $key = 'formie.upload.' . $assetId;

            if (isset($this->_lockedUploads[$assetId]) || !$mutex->acquire($key, 0)) {
                continue;
            }

            try {
                if ($this->isReferenced($assetId)) {
                    continue;
                }

                $eligible = ['and', ['id' => $row['id'], 'state' => [SubmissionUploadStatus::STAGED->value, SubmissionUploadStatus::BOUND->value, SubmissionUploadStatus::EXPIRED->value, SubmissionUploadStatus::REJECTED->value]], $promotion, $expiry];

                if (!(new Query())->from(Table::FORMIE_PENDING_UPLOADS)->where($eligible)->exists()) {
                    continue;
                }
                // The asset lock protects eligibility; already-expired retries may update zero rows.
                Craft::$app->getDb()->createCommand()->update(Table::FORMIE_PENDING_UPLOADS, ['state' => SubmissionUploadStatus::EXPIRED->value], $eligible)->execute();

                if ($this->_deleteTrackedAsset($row)) {
                    $count++;
                }
            } finally {
                $mutex->release($key);
            }
        }

        return $count;
    }

    public function getTrackedUploadByAssetId(int $assetId, ?int $formId = null, ?string $fieldUid = null): ?array
    {
        if ($assetId <= 0) {
            return null;
        }

        $rows = $this->getUploadMetadata([$assetId], $formId, $fieldUid);

        return $rows[0] ?? null;
    }

    public function getUploadsForSubmissionDeletion(Submission $submission): array
    {
        return (new Query())->from(Table::FORMIE_PENDING_UPLOADS)->where([
            'submissionId' => (int)$submission->id,
            'formId' => (int)$submission->formId,
        ])->all();
    }

    public function getUploadsForFormDeletion(int $formId, array $submissionIds): array
    {
        if (!$submissionIds) {
            return [];
        }

        return (new Query())->from(Table::FORMIE_PENDING_UPLOADS)->where([
            'formId' => $formId,
            'submissionId' => array_map('intval', $submissionIds),
        ])->all();
    }

    public function deleteSubmissionUploads(array $uploads): void
    {
        foreach ($uploads as $upload) {
            $assetId = (int)$upload['assetId'];
            $mutex = Craft::$app->getMutex();
            $key = 'formie.upload.' . $assetId;

            // Preserve intent even if a binding owns the asset lock. Craft's enclosing
            // submission-delete transaction rolls back this insert and the FK cascade together.
            if (!$this->getTrackedUploadByAssetId($assetId) && Asset::find()->id($assetId)->status(null)->trashed(null)->exists()) {
                unset($upload['id']);
                $upload['submissionId'] = null;
                $upload['state'] = SubmissionUploadStatus::EXPIRED->value;
                $upload['expiresAt'] = time();
                $upload['isFinalized'] = false;
                $upload['capabilities'] = null;

                if ($upload['promotionState'] === 'moved') {
                    $upload['promotionState'] = null;
                }
                Craft::$app->getDb()->createCommand()->insert(Table::FORMIE_PENDING_UPLOADS, $upload)->execute();
            }

            if (isset($this->_lockedUploads[$assetId]) || !$mutex->acquire($key, 0)) {
                continue;
            }

            try {
                $tracked = $this->getTrackedUploadByAssetId($assetId);

                if (!$tracked || $tracked['uid'] !== $upload['uid'] || $this->isReferenced(
                    $assetId,
                    (int)$upload['submissionId'],
                    $upload['contentKey'],
                )) {
                    continue;
                }
                $this->_deleteTrackedAsset($tracked);
            } finally {
                $mutex->release($key);
            }
        }
    }

    public function withUploadLocks(Submission $submission, callable $callback): mixed
    {
        $ids = [];

        foreach ($submission->getFieldValuesForField(FileUpload::class) as $value) {
            $ids = array_merge($ids, $this->_extractAssetIds($value));
        }
        $ids = array_unique($ids);
        sort($ids, SORT_NUMERIC);
        $acquired = [];
        $mutex = Craft::$app->getMutex();

        try {
            foreach ($ids as $id) {
                if (!$mutex->acquire('formie.upload.' . $id, 5)) {
                    throw new RuntimeException('Upload is busy. Please retry.');
                }
                $acquired[] = $id;
                $this->_lockedUploads[$id] = true;
            }
            return $callback();
        } finally {
            foreach (array_reverse($acquired) as $id) {
                unset($this->_lockedUploads[$id]);
                $mutex->release('formie.upload.' . $id);
            }
        }
    }

    public function stageAccepted(SubmissionCommand $command): bool
    {
        $submission = $command->submission;
        $fields = [];
        $before = [];

        foreach ($submission->getFieldValuesForField(FileUpload::class) as $key => $value) {
            $field = FileUploadRetentionHelper::resolveFileUploadFieldForContentKey($submission->getForm(), $key);

            if ($field) {
                $fields[] = $field;
                $before[$key] = $this->_extractAssetIds($value);

                if (!$field->beforeElementSave($submission, !$submission->id)) {
                    return false;
                }
            }
        }

        foreach ($fields as $field) {
            $field->stageUploads($submission);
        }

        // Multipart/Base64 files are created by this trusted request rather than a prior
        // browser upload. Give them the same explicit claim consumed by final binding.
        foreach ($submission->getFieldValuesForField(FileUpload::class) as $contentKey => $value) {
            $field = FileUploadRetentionHelper::resolveFileUploadFieldForContentKey($submission->getForm(), $contentKey);

            foreach (array_diff($this->_extractAssetIds($value), $before[$contentKey] ?? []) as $assetId) {
                $upload = $field ? $this->getTrackedUploadByAssetId((int)$assetId, (int)$command->form->id, $field->uid) : null;

                if (!$field || !$upload) {
                    throw new ForbiddenHttpException('Staged upload authorization could not be established.');
                }
                $this->_assertAttachableUpload($upload, $submission, $field, $contentKey);
                $command->uploadClaims->add($this->_claimFromRow($upload));
            }
        }

        return !$submission->hasErrors();
    }

    public function bindAccepted(SubmissionCommand $command): array
    {
        $submission = $command->submission;
        $form = $command->form;
        $existing = $submission->id ? Submission::find()->id($submission->id)->siteId((int)$form->siteId)->isIncomplete(null)->isSpam(null)->status(null)->one() : null;
        $toBind = [];
        $canAttachOwnedUpload = in_array($command->authority->type, [
            SubmissionAuthorityType::CONTROL_PANEL,
            SubmissionAuthorityType::GRAPHQL_ADMIN,
            SubmissionAuthorityType::TRUSTED_INTERNAL,
        ], true);

        foreach ($submission->getFieldValuesForField(FileUpload::class) as $contentKey => $value) {
            $field = FileUploadRetentionHelper::resolveFileUploadFieldForContentKey($form, $contentKey);

            if (!$field) {
                continue;
            }
            $ids = $this->_extractAssetIds($value);

            if ($field->limitFiles && count($ids) > (int)$field->limitFiles) {
                throw new ForbiddenHttpException('Upload count limit exceeded.');
            }
            $retained = $existing ? $this->_extractAssetIds($existing->getFieldValue($contentKey)) : [];

            foreach ($ids as $id) {
                // Stable Formie 3 ID payloads are accepted only after proving actual ownership.
                $upload = $this->getTrackedUploadByAssetId($id, (int)$form->id, $field->uid);
                $uploadSubmissionId = $upload && $upload['submissionId'] !== null ? (int)$upload['submissionId'] : null;
                $availableState = $upload && (
                    $upload['state'] === SubmissionUploadStatus::STAGED->value
                    || ($upload['state'] === SubmissionUploadStatus::BOUND->value
                        && $submission->id
                        && $uploadSubmissionId === (int)$submission->id)
                );
                $owned = $upload && (int)$upload['siteId'] === (int)$form->siteId
                    && $upload['contentKey'] === $contentKey
                    && hash_equals((string)$upload['browserHash'], Formie::$plugin->getSubmissionGrants()->browserHash($form))
                    && (int)$upload['expiresAt'] > time() && $availableState
                    && (!$uploadSubmissionId || $uploadSubmissionId === (int)$submission->id);
                $retainedAsset = in_array($id, $retained, true);
                $claim = $command->uploadClaims->get($contentKey, $id);
                $legacyOwned = $command->allowLegacyUploadIds && $owned;

                if ($command->operation === SubmissionOperation::PAYMENT_REPLAY && !$retainedAsset) {
                    throw new ForbiddenHttpException('Payment replay cannot introduce an upload.');
                }

                if (!$retainedAsset && !$claim && !$legacyOwned && !$canAttachOwnedUpload) {
                    throw new ForbiddenHttpException('A valid upload attachment capability is required.');
                }

                if ($claim && (!$upload || $claim->uploadId !== (int)$upload['id'] || !$owned)) {
                    throw new ForbiddenHttpException('Upload authorization no longer matches the staged asset.');
                }

                // Trusted workflows may omit a browser capability, but they never bypass
                // Formie ownership, field scope, expiry, or lifecycle-state checks.
                if (!$retainedAsset && !$owned) {
                    throw new ForbiddenHttpException('Invalid upload ownership.');
                }
                $asset = Asset::find()->id($id)->status(null)->one();

                if (!$asset || $field->exceedsMaxUploadSize((int)$asset->size) || ($field->sizeMinLimit && $asset->size < $field->sizeMinLimit * 1000000) || $field->getUploadTypeValidationErrors($asset->filename, $asset->getCopyOfFile())) {
                    throw new ForbiddenHttpException('Upload policy rejected the file.');
                }

                if ($owned && $upload['state'] === SubmissionUploadStatus::STAGED->value && !$retainedAsset) {
                    $toBind[$id] = $claim ?? $this->_claimFromRow($upload);
                }
            }
        }
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            foreach ($toBind as $id => $claim) {
                $updated = Craft::$app->getDb()->createCommand()->update(Table::FORMIE_PENDING_UPLOADS, [
                    'state' => SubmissionUploadStatus::BOUND->value, 'submissionId' => $submission->id,
                ], ['and', [
                    'id' => $claim->uploadId,
                    'uid' => $claim->uploadUid,
                    'assetId' => $claim->assetId,
                    'formId' => $claim->formId,
                    'siteId' => $claim->siteId,
                    'fieldUid' => $claim->fieldUid,
                    'contentKey' => $claim->contentKey,
                    'browserHash' => $claim->browserHash,
                    'submissionId' => $claim->submissionId,
                    'progressId' => $claim->progressId,
                    'state' => SubmissionUploadStatus::STAGED->value,
                ], ['>', 'expiresAt', time()]])->execute();

                if ($updated !== 1) {
                    throw new ForbiddenHttpException('Upload is no longer available.');
                }
            }
            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
        return array_map('intval', array_keys($toBind));
    }

    public function releaseUnpersistedBindings(array $assetIds): void
    {
        foreach ($assetIds as $assetId) {
            if (!$this->isReferenced((int)$assetId)) {
                Craft::$app->getDb()->createCommand()->update(Table::FORMIE_PENDING_UPLOADS, [
                    'state' => SubmissionUploadStatus::STAGED->value, 'submissionId' => null,
                ], ['assetId' => (int)$assetId, 'state' => SubmissionUploadStatus::BOUND->value])->execute();
            }
        }
    }

    public function bindPersisted(Submission $submission): void
    {
        foreach ($submission->getFieldValuesForField(FileUpload::class) as $contentKey => $value) {
            foreach ($this->_extractAssetIds($value) as $id) {
                Craft::$app->getDb()->createCommand()->update(Table::FORMIE_PENDING_UPLOADS, [
                    'submissionId' => (int)$submission->id, 'state' => SubmissionUploadStatus::BOUND->value,
                ], ['assetId' => $id, 'formId' => (int)$submission->formId, 'contentKey' => $contentKey, 'state' => [SubmissionUploadStatus::STAGED->value, SubmissionUploadStatus::BOUND->value]])->execute();
            }
        }
    }

    public function releaseRemoved(Submission $submission): void
    {
        $rows = (new Query())->from(Table::FORMIE_PENDING_UPLOADS)->where(['submissionId' => $submission->id, 'state' => [SubmissionUploadStatus::BOUND->value, SubmissionUploadStatus::FINALIZED->value]])->all();

        foreach ($rows as $row) {
            if (!$this->isReferenced((int)$row['assetId'])) {
                Craft::$app->getDb()->createCommand()->update(Table::FORMIE_PENDING_UPLOADS, [
                    'state' => SubmissionUploadStatus::STAGED->value, 'submissionId' => null, 'promotionState' => $row['promotionState'] === 'moving' ? 'moving' : null,
                    'isFinalized' => false, 'expiresAt' => time(), 'capabilities' => null,
                ], ['id' => $row['id']])->execute();
            }
        }
    }

    public function promoteAccepted(Submission $submission): void
    {
        $form = $submission->getForm();

        foreach ($submission->getFieldValuesForField(FileUpload::class) as $contentKey => $value) {
            $field = FileUploadRetentionHelper::resolveFileUploadFieldForContentKey($form, $contentKey);
            $ids = $this->_extractAssetIds($value);

            if (!$field || !$ids) {
                continue;
            }
            $folder = $field->getUploadFolderForSubmission($submission);

            foreach (Asset::find()->id($ids)->status(null)->all() as $index => $asset) {
                // Authorized retained Formie 3 relations acquire lifecycle records on first revision.
                $tracked = $this->getTrackedUploadByAssetId((int)$asset->id);

                if (!$tracked) {
                    $this->trackSubmissionAsset($asset, (int)$form->id, (int)$submission->id, $field->uid, $form, $contentKey);
                    $this->bindPersisted($submission);
                } elseif ($tracked['siteId'] === null || $tracked['contentKey'] === null) {
                    // Legacy metadata alone grants nothing. The accepted persisted field relation
                    // has already proved ownership at the command boundary before this adoption.
                    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_PENDING_UPLOADS, [
                        'formId' => (int)$form->id, 'siteId' => (int)$form->siteId,
                        'submissionId' => (int)$submission->id, 'fieldUid' => $field->uid,
                        'contentKey' => $contentKey, 'state' => SubmissionUploadStatus::BOUND->value,
                        'browserHash' => Formie::$plugin->getSubmissionGrants()->browserHash($form),
                        'expiresAt' => $this->_uploadExpiry(),
                        'capabilities' => null,
                    ], ['id' => $tracked['id']])->execute();
                }
                $filename = null;

                if ($field->filenameFormat && ($format = References::parseContent($field->filenameFormat, $submission))) {
                    $filename = Assets::prepareAssetName($format . ($index ? '_' . $index : '') . '.' . $asset->getExtension());
                }
                $this->promote($asset, $folder, $filename);
            }
        }
    }

    public function recoverPromotions(int $submissionId): void
    {
        // Recovery completes durable intents only. It never dispatches notifications or retries payments.
        $rows = (new Query())->from(Table::FORMIE_PENDING_UPLOADS)->where([
            'submissionId' => $submissionId, 'promotionState' => 'moving', 'state' => SubmissionUploadStatus::BOUND->value,
        ])->all();

        foreach ($rows as $row) {
            $asset = Asset::find()->id((int)$row['assetId'])->status(null)->one();
            $folder = Craft::$app->getAssets()->getFolderById((int)$row['promotionFolderId']);

            if (!$asset || !$folder || !$this->isReferenced((int)$row['assetId'])) {
                throw new RuntimeException('Upload promotion requires reconciliation.');
            }
            $this->promote($asset, $folder);
        }
    }

    public function promote(Asset $asset, VolumeFolder $folder, ?string $filename = null): void
    {
        $id = (int)$asset->id;

        if (isset($this->_lockedUploads[$id])) {
            $this->_promote($asset, $folder, $filename);
            return;
        }
        $mutex = Craft::$app->getMutex();
        $key = 'formie.upload.' . $id;

        if (!$mutex->acquire($key, 5)) {
            throw new RuntimeException('Upload promotion is busy.');
        }
        $this->_lockedUploads[$id] = true;

        try {
            $this->_promote($asset, $folder, $filename);
        } finally {
            unset($this->_lockedUploads[$id]);
            $mutex->release($key);
        }
    }

    public function pruneExpiredFieldAssets(mixed $consoleInstance = null): int
    {
        $forms = Form::find()->withoutCpIndexScope()->site('*')->unique()->status(null)->all();
        $purgedAssetCount = 0;

        foreach ($forms as $form) {
            $fields = FileUploadRetentionHelper::collectFieldsWithAssetRetention($form);

            if (!$fields) {
                continue;
            }

            foreach ($fields as $field) {
                $cutoff = DataRetentionHelper::subtractInterval(
                    new DateTime(),
                    $field->assetDataRetention,
                    (int)$field->assetDataRetentionValue,
                );

                if (!$cutoff) {
                    continue;
                }

                if ($consoleInstance) {
                    $consoleInstance->stdout(Craft::t('formie', 'Starting file upload asset retention for form “{f}”, field “{field}”: before {d}.', [
                        'f' => $form->handle,
                        'field' => $field->handle,
                        'd' => Db::prepareDateForDb($cutoff),
                    ]) . PHP_EOL, Console::FG_YELLOW);
                }

                $submissions = Submission::find()
                    ->formId((int)$form->id)
                    ->anyStatus()
                    ->status(null)
                    ->isIncomplete(null)
                    ->isSpam(null)
                    ->dateCreated('< ' . $cutoff->format('Y-m-d H:i:s'))
                    ->all();

                foreach ($submissions as $submission) {
                    foreach ($this->_resolveContentKeysForField($submission, $form, $field) as $contentKey) {
                        $purgedAssetCount += $this->_purgeSubmissionFieldAssets($submission, $contentKey);
                    }
                }
            }
        }

        return $purgedAssetCount;
    }

    public function isReferenced(int $assetId, ?int $exceptSubmissionId = null, ?string $exceptContentKey = null): bool
    {
        if ((new Query())->from('{{%relations}}')->where(['targetId' => $assetId])->andFilterWhere(['not', ['sourceId' => $exceptSubmissionId]])->exists()) {
            return true;
        }

        if ((new Query())->from(Table::FORMIE_RELATIONS)->where(['targetId' => $assetId])->andFilterWhere(['not', ['sourceId' => $exceptSubmissionId]])->exists()) {
            return true;
        }

        // Formie stores field values in JSON rather than Craft relation rows.
        foreach (Submission::find()->site('*')->unique()->isIncomplete(null)->isSpam(null)->status(null)->trashed(null)->each() as $submission) {
            if ($exceptSubmissionId === (int)$submission->id && $exceptContentKey === null) {
                continue;
            }

            foreach ($submission->getFieldValuesForField(FileUpload::class) as $contentKey => $value) {
                if ($exceptSubmissionId === (int)$submission->id && $exceptContentKey === $contentKey) {
                    continue;
                }

                if (in_array($assetId, $this->_extractAssetIds($value), true)) {
                    return true;
                }
            }
        }
        return false;
    }


    // Private Methods
    // =========================================================================

    private function _assertAttachableUpload(array $upload, Submission $submission, FileUpload $field, string $contentKey): void
    {
        $form = $submission->getForm();

        if (!$form) {
            throw new ForbiddenHttpException('Upload submission form is unavailable.');
        }
        $submissionId = $submission->id ? (int)$submission->id : null;
        $uploadSubmissionId = $upload['submissionId'] === null ? null : (int)$upload['submissionId'];
        $progressId = $upload['progressId'] === null ? null : (int)$upload['progressId'];
        $progress = $progressId ? Formie::$plugin->getSubmissionProgress()->getProgressState($form) : null;
        $isStaged = $upload['state'] === SubmissionUploadStatus::STAGED->value;
        $isRetainedBoundUpload = $upload['state'] === SubmissionUploadStatus::BOUND->value
            && $submissionId
            && $uploadSubmissionId === $submissionId
            && $this->_isRetainedSubmissionUpload((int)$upload['assetId'], $submissionId, (int)$upload['siteId'], $contentKey);
        $availableState = $upload['state'] === SubmissionUploadStatus::STAGED->value
            || $isRetainedBoundUpload;

        $matches = (int)$upload['formId'] === (int)$form->id
            && (int)$upload['siteId'] === (int)$form->siteId
            && hash_equals((string)$upload['fieldUid'], (string)$field->uid)
            && hash_equals((string)$upload['contentKey'], $contentKey)
            && hash_equals((string)$upload['browserHash'], Formie::$plugin->getSubmissionGrants()->browserHash($form))
            && $uploadSubmissionId === $submissionId
            && (int)$upload['expiresAt'] > time()
            && $availableState
            && (!$progressId || ($progress && (int)$progress->id === $progressId && $progress->submissionId === $submissionId));

        $asset = $matches ? Asset::find()->id((int)$upload['assetId'])->status(null)->one() : null;
        $stagingFolder = $asset && $isStaged ? $this->getStagingFolder() : null;
        $validLocation = $asset && ($isRetainedBoundUpload || ($stagingFolder
            && (int)$asset->folderId === (int)$stagingFolder->id
            && (int)$asset->volumeId === (int)$stagingFolder->volumeId));

        if (!$validLocation) {
            throw new ForbiddenHttpException('Upload is not available for this submission field.');
        }
    }

    private function _isRetainedSubmissionUpload(int $assetId, int $submissionId, int $siteId, string $contentKey): bool
    {
        $persisted = Submission::find()
            ->id($submissionId)
            ->siteId($siteId)
            ->isIncomplete(null)
            ->isSpam(null)
            ->status(null)
            ->one();

        if (!$persisted) {
            return false;
        }

        $value = $persisted->getFieldValuesForField(FileUpload::class)[$contentKey] ?? null;

        return in_array($assetId, $this->_extractAssetIds($value), true);
    }

    private function _claimFromRow(array $upload): SubmissionUploadClaim
    {
        return new SubmissionUploadClaim(
            (int)$upload['id'],
            (string)$upload['uid'],
            (int)$upload['assetId'],
            (int)$upload['formId'],
            (int)$upload['siteId'],
            (string)$upload['fieldUid'],
            (string)$upload['contentKey'],
            (string)$upload['browserHash'],
            $upload['submissionId'] === null ? null : (int)$upload['submissionId'],
            $upload['progressId'] === null ? null : (int)$upload['progressId'],
        );
    }

    private function _uploadExpiry(): int
    {
        $days = Formie::$plugin->getSettings()->maxIncompleteSubmissionAge;

        return time() + 86400 * min(30, $days > 0 ? $days : 30);
    }

    private function _promote(Asset $asset, VolumeFolder $folder, ?string $filename = null): void
    {
        $row = $this->getTrackedUploadByAssetId((int)$asset->id);

        if (!$row || !in_array($row['state'], [SubmissionUploadStatus::BOUND->value, SubmissionUploadStatus::FINALIZED->value], true)) {
            throw new RuntimeException('Upload must be bound before promotion.');
        }
        $db = Craft::$app->getDb();
        $targetVolume = $folder->getVolume();
        $filename = $row['promotionState'] === 'moving' ? $row['promotionFilename'] : ($filename ?: $asset->filename);
        $targetPath = ($folder->path ? rtrim($folder->path, '/') . '/' : '') . $filename;

        if ($row['promotionState'] !== 'moving' && ((int)$asset->folderId !== (int)$folder->id || $filename !== $asset->filename) && $targetVolume->fileExists($targetPath)) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $extension = $extension ? '.' . $extension : '';
            // Random UIDs may end in punctuation that Craft removes during a move.
            // Preserve the complete stable suffix even when the original name fills 255 bytes.
            $suffix = '_' . substr(hash('sha256', $row['uid']), 0, 12);
            $basename = mb_strcut(pathinfo($filename, PATHINFO_FILENAME), 0, 255 - strlen($suffix . $extension));
            $filename = Assets::prepareAssetName($basename . $suffix . $extension);
            $targetPath = ($folder->path ? rtrim($folder->path, '/') . '/' : '') . $filename;
        }
        $contentHash = $row['promotionState'] === 'moving' ? $row['contentHash'] : hash_file('sha256', $asset->getCopyOfFile());
        // Record an exact destination and content fingerprint before the provider write.
        // This also resolves a crash between a successful filesystem move and Craft's DB commit.
        $db->createCommand()->update(Table::FORMIE_PENDING_UPLOADS, [
            'promotionFolderId' => $folder->id, 'promotionFilename' => $filename,
            'promotionSourceFolderId' => $row['promotionSourceFolderId'] ?: $asset->folderId,
            'contentHash' => $contentHash, 'promotionState' => 'moving', 'failureCode' => null,
        ], ['assetId' => $asset->id])->execute();

        try {
            if ((int)$asset->folderId !== (int)$folder->id || $asset->filename !== $filename) {
                if ($targetVolume->fileExists($targetPath)) {
                    $copy = Assets::tempFilePath($filename);

                    try {
                        Assets::downloadFile($targetVolume, $targetPath, $copy);

                        if (!hash_equals((string)$contentHash, hash_file('sha256', $copy))) {
                            throw new RuntimeException('Upload destination requires reconciliation.');
                        }
                    } finally {
                        if (is_file($copy)) {
                            unlink($copy);
                        }
                    }
                    $sourceVolume = $asset->getVolume();
                    $sourcePath = $asset->getPath();

                    if (($sourceVolume->id !== $targetVolume->id || $sourcePath !== $targetPath) && $sourceVolume->fileExists($sourcePath)) {
                        $sourceVolume->deleteFile($sourcePath);

                        if ($sourceVolume->fileExists($sourcePath)) {
                            throw new RuntimeException('Upload source cleanup did not complete.');
                        }
                    }
                    // Keep the old filename in durable asset metadata until source cleanup
                    // finishes, so an interrupted recovery does not lose that exact path.
                    $db->createCommand()->update('{{%assets}}', [
                        'folderId' => $folder->id, 'volumeId' => $folder->volumeId, 'filename' => $filename,
                    ], ['id' => $asset->id])->execute();
                    $asset->folderId = $folder->id;
                    $asset->folderPath = $folder->path;
                    $asset->setVolumeId($folder->volumeId);
                    $asset->setFilename($filename);
                } else {
                    $asset->avoidFilenameConflicts = false;

                    if (!Craft::$app->getAssets()->moveAsset($asset, $folder, $filename)) {
                        throw new RuntimeException('Upload promotion could not be persisted.');
                    }
                }
            }

            if (!$targetVolume->fileExists($targetPath)) {
                throw new RuntimeException('Upload destination is unavailable.');
            }
            $db->createCommand()->update(Table::FORMIE_PENDING_UPLOADS, [
                'promotionState' => 'moved',
            ], ['assetId' => $asset->id])->execute();
        } catch (Throwable $e) {
            Craft::$app->getDb()->createCommand()->update(Table::FORMIE_PENDING_UPLOADS, [
                'failureCode' => 'promotionFailed',
            ], ['assetId' => $asset->id])->execute();
            throw $e;
        }
    }

    private function _resolveContentKeysForField(Submission $submission, Form $form, FileUpload $targetField): array
    {
        $keys = [];

        foreach ($submission->getFieldValuesForField(FileUpload::class) as $contentKey => $value) {
            $field = FileUploadRetentionHelper::resolveFileUploadFieldForContentKey($form, $contentKey);

            if (!$field || $field->uid !== $targetField->uid) {
                continue;
            }

            if (!$this->_fieldValueHasAssets($value)) {
                continue;
            }

            $keys[] = $contentKey;
        }

        return $keys;
    }

    private function _purgeSubmissionFieldAssets(Submission $submission, string $contentKey): int
    {
        $value = $submission->getFieldValue($contentKey);
        $assetIds = $this->_extractAssetIds($value);

        if (!$assetIds) {
            return 0;
        }

        $field = FileUploadRetentionHelper::resolveFileUploadFieldForContentKey($submission->getForm(), $contentKey);
        $remaining = $assetIds;
        $purged = 0;

        foreach ($assetIds as $assetId) {
            $mutex = Craft::$app->getMutex();
            $key = 'formie.upload.' . $assetId;

            if (isset($this->_lockedUploads[$assetId]) || !$mutex->acquire($key, 0)) {
                continue;
            }

            try {
                $upload = $this->getTrackedUploadByAssetId($assetId, (int)$submission->formId, $field?->uid);
                $owned = $upload && $field && (int)$upload['submissionId'] === (int)$submission->id
                    && ($upload['contentKey'] === null || $upload['contentKey'] === $contentKey);

                // Retention of a relation does not grant ownership of a shared or imported asset.
                if ($owned && !$this->isReferenced($assetId, (int)$submission->id, $contentKey)) {
                    if (!$this->_deleteTrackedAsset($upload)) {
                        continue;
                    }
                    $purged++;
                }

                $remaining = array_values(array_diff($remaining, [$assetId]));
            } finally {
                $mutex->release($key);
            }
        }

        if ($remaining !== $assetIds) {
            $submission->setFieldValue($contentKey, $remaining);
            $this->_persistSubmissionContent($submission);
        }

        return $purged;
    }

    private function _deleteTrackedAsset(array $upload): bool
    {
        $assetId = (int)$upload['assetId'];
        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            $asset = Asset::find()->id($assetId)->status(null)->trashed(null)->one();

            if ($asset) {
                $volume = $asset->getVolume();
                $path = $asset->getPath();

                if (!$volume->fileExists($path)) {
                    $asset->keepFileOnDelete = true;
                }

                if (!Craft::$app->getElements()->deleteElement($asset, true)) {
                    throw new RuntimeException('Asset deletion did not complete.');
                }
                $this->_deletePromotionDestination($upload, $asset);

                if ($volume->fileExists($path)) {
                    throw new RuntimeException('Asset deletion did not complete.');
                }
            }
            $db->createCommand()->delete(Table::FORMIE_PENDING_UPLOADS, ['id' => $upload['id']])->execute();
            $transaction->commit();
        } catch (Throwable) {
            // Roll back metadata, not the filesystem. A retry can finish an already-removed file.
            $transaction->rollBack();
            $db->createCommand()->update(Table::FORMIE_PENDING_UPLOADS, [
                'failureCode' => 'deletionFailed',
                'dateUpdated' => gmdate('Y-m-d H:i:s'),
            ], ['id' => $upload['id']])->execute();
            Formie::error("Failed to delete tracked upload asset #{$assetId}; cleanup will retry.");

            return false;
        }

        return true;
    }

    private function _deletePromotionDestination(array $upload, Asset $asset): void
    {
        if ($upload['promotionState'] !== 'moving') {
            return;
        }
        $folder = Craft::$app->getAssets()->getFolderById((int)$upload['promotionFolderId']);

        if (!$folder || !$upload['promotionFilename'] || !$upload['contentHash']) {
            throw new RuntimeException('Upload promotion requires reconciliation.');
        }
        $volume = $folder->getVolume();
        $path = ($folder->path ? rtrim($folder->path, '/') . '/' : '') . $upload['promotionFilename'];

        if (!$volume->fileExists($path)) {
            return;
        }
        $stream = $volume->getFileStream($path);

        try {
            $hash = hash_init('sha256');
            hash_update_stream($hash, $stream);

            if (!hash_equals((string)$upload['contentHash'], hash_final($hash))) {
                throw new RuntimeException('Upload destination requires reconciliation.');
            }
        } finally {
            fclose($stream);
        }

        if ($volume->id === $asset->getVolume()->id && $path === $asset->getPath()) {
            return;
        }
        $volume->deleteFile($path);

        if ($volume->fileExists($path)) {
            throw new RuntimeException('Upload destination deletion did not complete.');
        }
    }

    private function _persistSubmissionContent(Submission $submission): void
    {
        if (!$submission->id) {
            return;
        }

        $record = SubmissionRecord::findOne($submission->id);

        if (!$record) {
            return;
        }

        $record->content = $submission->serializeFieldValues();
        $record->save(false);
    }

    private function _fieldValueHasAssets(mixed $value): bool
    {
        return $this->_extractAssetIds($value) !== [];
    }

    private function _extractAssetIds(mixed $value): array
    {
        if ($value instanceof AssetQuery) {
            return array_values(array_filter(array_map('intval', $value->ids())));
        }

        if ($value instanceof Asset) {
            return [(int)$value->id];
        }

        if (is_array($value)) {
            $assetIds = [];

            foreach ($value as $item) {
                if ($item instanceof Asset) {
                    $assetIds[] = (int)$item->id;
                    continue;
                }

                if (is_numeric($item)) {
                    $assetIds[] = (int)$item;
                }
            }

            return array_values(array_unique(array_filter($assetIds)));
        }

        return [];
    }
}
