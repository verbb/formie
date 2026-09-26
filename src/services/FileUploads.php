<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\FileUpload;
use verbb\formie\helpers\DataRetentionHelper;
use verbb\formie\helpers\FileUploadRetentionHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\SubmissionCommand;
use verbb\formie\records\Submission as SubmissionRecord;

use Craft;
use craft\db\Query;
use craft\elements\Asset;
use craft\elements\db\AssetQuery;
use craft\helpers\Console;
use craft\helpers\Db;

use DateTime;
use Throwable;

use yii\base\Component;

class FileUploads extends Component
{
    // Properties
    // =========================================================================

    private array $_lockedUploads = [];


    // Public Methods
    // =========================================================================

    public function trackSubmissionAsset(Asset $asset, int $formId, ?int $submissionId, ?string $fieldUid = null, ?Form $form = null, ?string $contentKey = null): void
    {
        $form ??= Form::find()->id($formId)->site('*')->status(null)->one();
        if (!$form) {
            throw new \InvalidArgumentException('Upload form is unavailable.');
        }
        $now = gmdate('Y-m-d H:i:s');
        $existing = (new Query())
            ->select(['id'])
            ->from(Table::FORMIE_PENDING_UPLOADS)
            ->where(['assetId' => (int)$asset->id])
            ->scalar();

        $values = [
            'assetId' => (int)$asset->id,
            'formId' => $formId > 0 ? $formId : null,
            'submissionId' => $submissionId > 0 ? $submissionId : null,
            'fieldUid' => $fieldUid,
            'isFinalized' => false,
            'state' => 'staged',
            'siteId' => (int)$form->siteId,
            'browserHash' => Formie::$plugin->getSubmissionGrants()->browserHash($form),
            'contentKey' => $contentKey,
            'expiresAt' => time() + 86400 * ((int)Formie::$plugin->getSettings()->maxIncompleteSubmissionAge ?: 30),
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
        if (!$submission) {
            return;
        }
        $accepted = [];
        foreach ($submission->getFieldValuesForField(FileUpload::class) as $value) {
            $accepted = array_merge($accepted, $this->_extractAssetIds($value));
        }
        Craft::$app->getDb()->createCommand()->update(Table::FORMIE_PENDING_UPLOADS, [
            'state' => 'finalized', 'isFinalized' => true, 'dateUpdated' => gmdate('Y-m-d H:i:s'),
        ], ['submissionId' => $submissionId, 'assetId' => $accepted, 'state' => 'bound'])->execute();
    }

    public function removeUploadByAssetId(int $assetId, ?int $formId = null, ?string $fieldUid = null): bool
    {
        if ($assetId <= 0) {
            return false;
        }

        $upload = $this->getTrackedUploadByAssetId($assetId, $formId, $fieldUid);

        if (!$upload || in_array($upload['state'], ['bound', 'finalized'], true) || $this->isReferenced($assetId)) {
            return false;
        }

        if (Craft::$app->getDb()->createCommand()->update(Table::FORMIE_PENDING_UPLOADS, ['state' => 'rejected', 'expiresAt' => time()], ['assetId' => $assetId, 'state' => 'staged'])->execute() !== 1) {
            return false;
        }
        Craft::$app->getElements()->deleteElementById($assetId, Asset::class, true);
        Craft::$app->getDb()->createCommand()
            ->delete(Table::FORMIE_PENDING_UPLOADS, ['assetId' => $assetId])
            ->execute();

        return true;
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
        $expiry = ['<=', 'expiresAt', time()];
        if ($olderThanTimestamp !== null) {
            $expiry = ['or', $expiry, ['<', 'dateUpdated', gmdate('Y-m-d H:i:s', $olderThanTimestamp)]];
        }
        $rows = (new Query())
            ->select(['id', 'assetId'])
            ->from(Table::FORMIE_PENDING_UPLOADS)
            ->where(['state' => ['staged', 'bound', 'expired', 'rejected']])
            ->andWhere(['promotionState' => null])
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

                if (Craft::$app->getDb()->createCommand()->update(Table::FORMIE_PENDING_UPLOADS, ['state' => 'expired'], ['and', ['id' => $row['id'], 'state' => ['staged', 'bound', 'expired', 'rejected'], 'promotionState' => null], $expiry])->execute() !== 1) {
                    continue;
                }
                if ($assetId > 0) {
                    Craft::$app->getElements()->deleteElementById($assetId, Asset::class, true);
                }

                Craft::$app->getDb()->createCommand()
                    ->delete(Table::FORMIE_PENDING_UPLOADS, ['id' => (int)$row['id']])
                    ->execute();

                $count++;
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
                    throw new \RuntimeException('Upload is busy. Please retry.');
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

    public function stageAccepted(Submission $submission): bool
    {
        $fields = [];
        foreach ($submission->getFieldValuesForField(FileUpload::class) as $key => $value) {
            $field = FileUploadRetentionHelper::resolveFileUploadFieldForContentKey($submission->getForm(), $key);
            if ($field) {
                $fields[] = $field;
                if (!$field->beforeElementSave($submission, !$submission->id)) {
                    return false;
                }
            }
        }
        foreach ($fields as $field) {
            $field->stageUploads($submission);
        }
        return !$submission->hasErrors();
    }

    public function bindAccepted(SubmissionCommand $command): array
    {
        $submission = $command->submission;
        $form = $command->form;
        $existing = $submission->id ? Submission::find()->id($submission->id)->siteId((int)$form->siteId)->isIncomplete(null)->isSpam(null)->status(null)->one() : null;
        $toBind = [];
        foreach ($submission->getFieldValuesForField(FileUpload::class) as $contentKey => $value) {
            $field = FileUploadRetentionHelper::resolveFileUploadFieldForContentKey($form, $contentKey);
            if (!$field) {
                continue;
            }
            $ids = $this->_extractAssetIds($value);
            if ($field->limitFiles && count($ids) > (int)$field->limitFiles) {
                throw new \yii\web\ForbiddenHttpException('Upload count limit exceeded.');
            }
            $retained = $existing ? $this->_extractAssetIds($existing->getFieldValue($contentKey)) : [];
            foreach ($ids as $id) {
                // Stable Formie 3 ID payloads are accepted only after proving actual ownership.
                $upload = $this->getTrackedUploadByAssetId($id, (int)$form->id, $field->uid);
                $owned = $upload && (int)$upload['siteId'] === (int)$form->siteId
                    && $upload['contentKey'] === $contentKey
                    && hash_equals((string)$upload['browserHash'], Formie::$plugin->getSubmissionGrants()->browserHash($form))
                    && (int)$upload['expiresAt'] > time() && $upload['state'] === 'staged'
                    && (!$upload['submissionId'] || (int)$upload['submissionId'] === (int)$submission->id);
                if (!in_array($id, $retained, true) && !$owned) {
                    throw new \yii\web\ForbiddenHttpException('Invalid upload ownership.');
                }
                $asset = Asset::find()->id($id)->status(null)->one();
                if (!$asset || $field->exceedsMaxUploadSize((int)$asset->size) || ($field->sizeMinLimit && $asset->size < $field->sizeMinLimit * 1000000) || $field->getUploadTypeValidationErrors($asset->filename, $asset->getCopyOfFile())) {
                    throw new \yii\web\ForbiddenHttpException('Upload policy rejected the file.');
                }
                if ($owned) {
                    $toBind[] = $id;
                }
            }
        }
        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            foreach ($toBind as $id) {
                $updated = Craft::$app->getDb()->createCommand()->update(Table::FORMIE_PENDING_UPLOADS, [
                    'state' => 'bound', 'submissionId' => $submission->id,
                ], ['and', ['assetId' => $id, 'state' => 'staged'], ['>', 'expiresAt', time()]])->execute();
                if ($updated !== 1) {
                    throw new \yii\web\ForbiddenHttpException('Upload is no longer available.');
                }
            }
            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
        return $toBind;
    }

    public function releaseUnpersistedBindings(array $assetIds): void
    {
        foreach ($assetIds as $assetId) {
            if (!$this->isReferenced((int)$assetId)) {
                Craft::$app->getDb()->createCommand()->update(Table::FORMIE_PENDING_UPLOADS, [
                    'state' => 'staged', 'submissionId' => null,
                ], ['assetId' => (int)$assetId, 'state' => 'bound'])->execute();
            }
        }
    }

    public function bindPersisted(Submission $submission): void
    {
        foreach ($submission->getFieldValuesForField(FileUpload::class) as $contentKey => $value) {
            foreach ($this->_extractAssetIds($value) as $id) {
                Craft::$app->getDb()->createCommand()->update(Table::FORMIE_PENDING_UPLOADS, [
                    'submissionId' => (int)$submission->id, 'state' => 'bound',
                ], ['assetId' => $id, 'formId' => (int)$submission->formId, 'contentKey' => $contentKey, 'state' => ['staged', 'bound']])->execute();
            }
        }
    }

    public function releaseRemoved(Submission $submission): void
    {
        $rows = (new Query())->from(Table::FORMIE_PENDING_UPLOADS)->where(['submissionId' => $submission->id, 'state' => ['bound', 'finalized']])->all();
        foreach ($rows as $row) {
            if (!$this->isReferenced((int)$row['assetId'])) {
                Craft::$app->getDb()->createCommand()->update(Table::FORMIE_PENDING_UPLOADS, [
                    'state' => 'staged', 'submissionId' => null, 'promotionState' => null,
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
                        'contentKey' => $contentKey, 'state' => 'bound',
                        'browserHash' => Formie::$plugin->getSubmissionGrants()->browserHash($form),
                        'expiresAt' => time() + 86400 * ((int)Formie::$plugin->getSettings()->maxIncompleteSubmissionAge ?: 30),
                        'capabilities' => null,
                    ], ['id' => $tracked['id']])->execute();
                }
                $filename = null;
                if ($field->filenameFormat && ($format = \verbb\formie\helpers\References::parseContent($field->filenameFormat, $submission))) {
                    $filename = \craft\helpers\Assets::prepareAssetName($format . ($index ? '_' . $index : '') . '.' . $asset->getExtension());
                }
                $this->promote($asset, $folder, $filename);
            }
        }
    }

    public function recoverPromotions(int $submissionId): void
    {
        // Recovery completes durable intents only. It never dispatches notifications or retries payments.
        $rows = (new Query())->from(Table::FORMIE_PENDING_UPLOADS)->where([
            'submissionId' => $submissionId, 'promotionState' => 'moving', 'state' => 'bound',
        ])->all();
        foreach ($rows as $row) {
            $asset = Asset::find()->id((int)$row['assetId'])->status(null)->one();
            $folder = Craft::$app->getAssets()->getFolderById((int)$row['promotionFolderId']);
            if (!$asset || !$folder || !$this->isReferenced((int)$row['assetId'])) {
                throw new \RuntimeException('Upload promotion requires reconciliation.');
            }
            $this->promote($asset, $folder);
        }
    }

    public function promote(Asset $asset, \craft\models\VolumeFolder $folder, ?string $filename = null): void
    {
        $id = (int)$asset->id;
        if (isset($this->_lockedUploads[$id])) {
            $this->_promote($asset, $folder, $filename);
            return;
        }
        $mutex = Craft::$app->getMutex();
        $key = 'formie.upload.' . $id;
        if (!$mutex->acquire($key, 5)) {
            throw new \RuntimeException('Upload promotion is busy.');
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

    public function isReferenced(int $assetId, ?int $exceptSubmissionId = null): bool
    {
        if ((new Query())->from('{{%relations}}')->where(['targetId' => $assetId])->andFilterWhere(['not', ['sourceId' => $exceptSubmissionId]])->exists()) {
            return true;
        }
        if ((new Query())->from(Table::FORMIE_RELATIONS)->where(['targetId' => $assetId])->andFilterWhere(['not', ['sourceId' => $exceptSubmissionId]])->exists()) {
            return true;
        }
        // Formie stores field values in JSON rather than Craft relation rows.
        foreach (Submission::find()->site('*')->unique()->isIncomplete(null)->isSpam(null)->status(null)->each() as $submission) {
            if ($exceptSubmissionId === (int)$submission->id) {
                continue;
            }
            foreach ($submission->getFieldValuesForField(FileUpload::class) as $value) {
                if (in_array($assetId, $this->_extractAssetIds($value), true)) {
                    return true;
                }
            }
        }
        return false;
    }

    // Private Methods
    // =========================================================================

    private function _promote(Asset $asset, \craft\models\VolumeFolder $folder, ?string $filename = null): void
    {
        $row = $this->getTrackedUploadByAssetId((int)$asset->id);
        if (!$row || !in_array($row['state'], ['bound', 'finalized'], true)) {
            throw new \RuntimeException('Upload must be bound before promotion.');
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
            $filename = \craft\helpers\Assets::prepareAssetName($basename . $suffix . $extension);
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
                    $copy = \craft\helpers\Assets::tempFilePath($filename);
                    try {
                        \craft\helpers\Assets::downloadFile($targetVolume, $targetPath, $copy);
                        if (!hash_equals((string)$contentHash, hash_file('sha256', $copy))) {
                            throw new \RuntimeException('Upload destination requires reconciliation.');
                        }
                    } finally {
                        if (is_file($copy)) {
                            unlink($copy);
                        }
                    }
                    $sourceVolume = $asset->getVolume();
                    $sourcePath = $asset->getPath();
                    // The intent owns this exact asset and destination. Repair only its metadata.
                    $db->createCommand()->update('{{%assets}}', [
                        'folderId' => $folder->id, 'volumeId' => $folder->volumeId, 'filename' => $filename,
                    ], ['id' => $asset->id])->execute();
                    if (($sourceVolume->id !== $targetVolume->id || $sourcePath !== $targetPath) && $sourceVolume->fileExists($sourcePath)) {
                        $sourceVolume->deleteFile($sourcePath);
                    }
                    $asset->folderId = $folder->id;
                    $asset->folderPath = $folder->path;
                    $asset->setVolumeId($folder->volumeId);
                    $asset->setFilename($filename);
                } else {
                    $asset->avoidFilenameConflicts = false;
                    if (!Craft::$app->getAssets()->moveAsset($asset, $folder, $filename)) {
                        throw new \RuntimeException('Upload promotion could not be persisted.');
                    }
                }
            }
            if (!$targetVolume->fileExists($targetPath)) {
                throw new \RuntimeException('Upload destination is unavailable.');
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

        $elementsService = Craft::$app->getElements();
        $purged = 0;

        foreach ($assetIds as $assetId) {
            try {
                $asset = Asset::find()->id($assetId)->status(null)->one();

                if ($this->isReferenced($assetId, (int)$submission->id)) {
                    continue;
                }
                if ($asset && $elementsService->deleteElement($asset, true)) {
                    $purged++;
                }

                Craft::$app->getDb()->createCommand()
                    ->delete(Table::FORMIE_PENDING_UPLOADS, ['assetId' => $assetId])
                    ->execute();
            } catch (Throwable $e) {
                Formie::error("Failed to purge uploaded asset #{$assetId} for submission #{$submission->id}: {$e->getMessage()}");
            }
        }

        if ($purged) {
            $submission->setFieldValue($contentKey, []);
            $this->_persistSubmissionContent($submission);
        }

        return $purged;
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
