<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\FileUpload;
use verbb\formie\helpers\DataRetentionHelper;
use verbb\formie\helpers\FileUploadRetentionHelper;
use verbb\formie\helpers\Table;
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
    // Public Methods
    // =========================================================================

    public function trackSubmissionAsset(Asset $asset, int $formId, ?int $submissionId, ?string $fieldUid = null): void
    {
        $now = date('Y-m-d H:i:s');
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
            'dateUpdated' => $now,
        ];

        if ($existing) {
            Craft::$app->getDb()->createCommand()
                ->update(Table::FORMIE_PENDING_UPLOADS, $values, ['id' => (int)$existing])
                ->execute();
        } else {
            $values['dateCreated'] = $now;
            $values['uid'] = Craft::$app->getSecurity()->generateRandomString(36);

            Craft::$app->getDb()->createCommand()
                ->insert(Table::FORMIE_PENDING_UPLOADS, $values)
                ->execute();
        }
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
        $this->trackSubmissionAsset($asset, (int)$form->id, $submissionId, $field->uid);
    }

    public function finalizeSubmissionUploads(int $submissionId): void
    {
        if ($submissionId <= 0) {
            return;
        }

        Craft::$app->getDb()->createCommand()
            ->update(Table::FORMIE_PENDING_UPLOADS, [
                'isFinalized' => true,
                'dateUpdated' => date('Y-m-d H:i:s'),
            ], ['submissionId' => $submissionId])
            ->execute();
    }

    public function removeUploadByAssetId(int $assetId, ?int $formId = null, ?string $fieldUid = null): bool
    {
        if ($assetId <= 0) {
            return false;
        }

        $upload = $this->getTrackedUploadByAssetId($assetId, $formId, $fieldUid);

        if (!$upload || !empty($upload['isFinalized'])) {
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
            ->select(['assetId', 'formId', 'submissionId', 'fieldUid', 'isFinalized', 'dateCreated', 'dateUpdated'])
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
        $settings = Formie::$plugin->getSettings();

        if ($olderThanTimestamp === null) {
            $olderThanTimestamp = time() - ((int)$settings->maxIncompleteSubmissionAge * 86400);
        }

        $olderThan = date('Y-m-d H:i:s', $olderThanTimestamp);
        $rows = (new Query())
            ->select(['id', 'assetId'])
            ->from(Table::FORMIE_PENDING_UPLOADS)
            ->where(['isFinalized' => false])
            ->andWhere(['<', 'dateUpdated', $olderThan])
            ->all();

        $count = 0;

        foreach ($rows as $row) {
            $assetId = (int)($row['assetId'] ?? 0);

            if ($assetId > 0) {
                Craft::$app->getElements()->deleteElementById($assetId, Asset::class, true);
            }

            Craft::$app->getDb()->createCommand()
                ->delete(Table::FORMIE_PENDING_UPLOADS, ['id' => (int)$row['id']])
                ->execute();

            $count++;
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

    public function pruneExpiredFieldAssets(mixed $consoleInstance = null): int
    {
        $forms = Form::find()->status(null)->all();
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

    /**
     * @return string[]
     */
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

    /**
     * @return int[]
     */
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
