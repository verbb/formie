<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\fields\FileUpload;
use verbb\formie\helpers\Table;

use Craft;
use craft\db\Query;
use craft\elements\Asset;
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

}
