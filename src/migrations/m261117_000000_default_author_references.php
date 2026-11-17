<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table as FormieTable;
use verbb\formie\integrations\elements\CalendarEvent;
use verbb\formie\integrations\elements\Entry;
use verbb\formie\integrations\elements\EventsEvent;
use verbb\formie\integrations\elements\Product;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

/**
 * Formie 3 saved an element integration's default author as a list of IDs such as `["10"]`. The form builder only
 * shows `{id, siteId}` references, so upgraded forms showed no author even though one was set.
 */
class m261117_000000_default_author_references extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $handles = (new Query())
            ->select(['handle'])
            ->from(FormieTable::FORMIE_INTEGRATIONS)
            ->where(['type' => [Entry::class, Product::class, CalendarEvent::class, EventsEvent::class]])
            ->column($this->db);

        if (!$handles) {
            return true;
        }

        $siteId = Craft::$app->getSites()->getPrimarySite()->id;

        foreach ((new Query())->select(['id', 'settings'])->from(FormieTable::FORMIE_FORMS)->each(100, $this->db) as $row) {
            $settings = Json::decodeIfJson($row['settings']);

            if (!is_array($settings)) {
                continue;
            }

            $changed = false;

            foreach ($handles as $handle) {
                $references = $this->_references($settings['integrations'][$handle]['defaultAuthorId'] ?? null, $siteId);

                if ($references !== null) {
                    $settings['integrations'][$handle]['defaultAuthorId'] = $references;
                    $changed = true;
                }
            }

            if ($changed) {
                $this->update(FormieTable::FORMIE_FORMS, ['settings' => Json::encode($settings)], ['id' => $row['id']], [], false);
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m261117_000000_default_author_references cannot be reverted.\n";

        return false;
    }


    // Private Methods
    // =========================================================================

    private function _references(mixed $value, int $siteId): ?array
    {
        $ids = is_array($value) ? $value : [$value];

        // Only a value made entirely of IDs is the Formie 3 shape. References and empty values stay as they are.
        if (!$ids || array_filter($ids, fn($id) => !is_numeric($id))) {
            return null;
        }

        return array_map(fn($id) => ['id' => (int)$id, 'siteId' => $siteId], array_values($ids));
    }
}
