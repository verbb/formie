<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\services\Stencils as StencilsService;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;
use craft\helpers\ProjectConfig;

class m261115_025000_unmigrated_stencils extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // An upgrade from Formie 2 can reach m240318 before its stencils can be read, leaving them with fields of unknown types.
        $before = $this->_stencilData();
        $unmigrated = array_filter($before, static fn(?string $data): bool => str_contains((string)$data, 'formfields'));

        if (!$unmigrated) {
            return true;
        }

        (new m240318_000000_migrate_stencils(['db' => $this->db]))->safeUp();

        // That migration reserializes every stencil, so only keep its changes to the ones it was needed for.
        foreach (array_diff_assoc($this->_stencilData(), $before) as $uid => $data) {
            if (!isset($unmigrated[$uid])) {
                $this->update(Table::FORMIE_STENCILS, ['data' => $before[$uid]], ['uid' => $uid], [], false);
            }
        }

        $repaired = array_diff_assoc(array_intersect_key($this->_stencilData(), $unmigrated), $unmigrated);
        $projectConfig = Craft::$app->getProjectConfig();

        // Config that has already reached this version carries the repaired data and is applied as it is.
        if (!$repaired || $projectConfig->readOnly || version_compare($projectConfig->get('plugins.formie.schemaVersion', true) ?? '0', Formie::$plugin->schemaVersion, '>=')) {
            return true;
        }

        foreach ($repaired as $uid => $data) {
            $path = StencilsService::CONFIG_STENCILS_KEY . '.' . $uid;

            if ($config = $projectConfig->get($path)) {
                $config = ProjectConfig::unpackAssociativeArray((array)$config);
                $projectConfig->set($path, ProjectConfig::packAssociativeArray(['data' => Json::decode($data)] + $config), 'Repair Formie stencils from Formie 2');
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m261115_025000_unmigrated_stencils cannot be reverted.\n";

        return false;
    }


    // Private Methods
    // =========================================================================

    private function _stencilData(): array
    {
        return (new Query())->select(['data', 'uid'])->from(Table::FORMIE_STENCILS)->indexBy('uid')->column($this->db);
    }
}
