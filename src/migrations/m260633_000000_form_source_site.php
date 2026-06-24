<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table as CraftTable;
use craft\helpers\Db;

class m260633_000000_form_source_site extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->columnExists(Table::FORMIE_FORMS, 'sourceSiteId')) {
            $this->addColumn(Table::FORMIE_FORMS, 'sourceSiteId', $this->integer()->after('groupId'));
        }

        $this->dropIndexIfExists(Table::FORMIE_FORMS, ['sourceSiteId']);
        $this->createIndex(null, Table::FORMIE_FORMS, 'sourceSiteId', false);

        $this->dropForeignKeyIfExists(Table::FORMIE_FORMS, ['sourceSiteId']);
        $this->addForeignKey(
            null,
            Table::FORMIE_FORMS,
            ['sourceSiteId'],
            '{{%sites}}',
            ['id'],
            'SET NULL',
            null,
        );

        if (!Craft::$app->getIsMultiSite()) {
            return true;
        }

        $primarySiteId = (int)Craft::$app->getSites()->getPrimarySite()->id;
        $formIds = (new Query())
            ->select(['id'])
            ->from([Table::FORMIE_FORMS])
            ->column();

        foreach ($formIds as $formId) {
            $formId = (int)$formId;
            $sourceSiteId = $this->_resolveBackfillSourceSiteId($formId, $primarySiteId);

            Db::update(Table::FORMIE_FORMS, ['sourceSiteId' => $sourceSiteId], ['id' => $formId]);
        }

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropForeignKeyIfExists(Table::FORMIE_FORMS, ['sourceSiteId']);

        if ($this->db->columnExists(Table::FORMIE_FORMS, 'sourceSiteId')) {
            $this->dropColumn(Table::FORMIE_FORMS, 'sourceSiteId');
        }

        return true;
    }


    // Private Methods
    // =========================================================================

    private function _resolveBackfillSourceSiteId(int $formId, int $primarySiteId): int
    {
        $primaryEnabled = (new Query())
            ->from([CraftTable::ELEMENTS_SITES])
            ->where([
                'elementId' => $formId,
                'siteId' => $primarySiteId,
                'enabled' => true,
            ])
            ->exists();

        if ($primaryEnabled) {
            return $primarySiteId;
        }

        $enabledSiteId = (new Query())
            ->select(['siteId'])
            ->from([CraftTable::ELEMENTS_SITES])
            ->where([
                'elementId' => $formId,
                'enabled' => true,
            ])
            ->orderBy(['siteId' => SORT_ASC])
            ->scalar();

        if ($enabledSiteId) {
            return (int)$enabledSiteId;
        }

        $anySiteId = (new Query())
            ->select(['siteId'])
            ->from([CraftTable::ELEMENTS_SITES])
            ->where(['elementId' => $formId])
            ->orderBy(['siteId' => SORT_ASC])
            ->scalar();

        return $anySiteId ? (int)$anySiteId : $primarySiteId;
    }
}
