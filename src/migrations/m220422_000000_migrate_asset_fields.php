<?php
namespace verbb\formie\migrations;

use verbb\formie\fields\formfields\FileUpload;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Json;

class m220422_000000_migrate_asset_fields extends Migration
{
    private array $_volumesByFolderUids = [];

    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->_volumesByFolderUids = (new Query())
            ->select(['folders.uid folderUid', 'volumes.uid volumeUid'])
            ->from(['volumes' => Table::VOLUMES])
            ->innerJoin(['folders' => Table::VOLUMEFOLDERS], '[[folders.volumeId]] = [[volumes.id]]')
            ->pairs();

        $fields = (new Query())
            ->select(['id', 'settings'])
            ->from([Table::FIELDS])
            ->where(['type' => FileUpload::class])
            ->all($this->db);

        foreach ($fields as $field) {
            $settings = Json::decode($field['settings']);

            $uploadLocationSource = $settings['uploadLocationSource'] ?? '';
            $restrictedLocationSource = $settings['restrictedLocationSource'] ?? '';
            $defaultUploadLocationSource = $settings['defaultUploadLocationSource'] ?? '';

            $settings['uploadLocationSource'] = $this->_normalizeSourceKey($uploadLocationSource);
            $settings['restrictedLocationSource'] = $this->_normalizeSourceKey($restrictedLocationSource);
            $settings['defaultUploadLocationSource'] = $this->_normalizeSourceKey($defaultUploadLocationSource);

            $this->update(Table::FIELDS, [
                'settings' => Json::encode($settings)
            ], ['id' => $field['id']], [], false);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m220422_000000_migrate_asset_fields cannot be reverted.\n";
        return false;
    }

    private function _normalizeSourceKey(string $sourceKey): string
    {
        if (empty($sourceKey) || !str_starts_with($sourceKey, 'folder:')) {
            return $sourceKey;
        }

        $parts = explode(':', $sourceKey);
        $folderUid = $parts[1];

        return array_key_exists($folderUid, $this->_volumesByFolderUids) ? 'volume:' . $this->_volumesByFolderUids[$folderUid] : str_replace('folder:', 'volume:', $sourceKey);
    }
}
