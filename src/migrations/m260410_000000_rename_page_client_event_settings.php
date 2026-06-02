<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m260410_000000_rename_page_client_event_settings extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $table = Table::FORMIE_FIELD_LAYOUT_PAGES;

        foreach ((new Query())->select(['id', 'settings'])->from($table)->each() as $row) {
            $settings = Json::decodeIfJson($row['settings'] ?? '{}');

            if (!is_array($settings)) {
                continue;
            }

            $changed = false;

            if (array_key_exists('enableJsEvents', $settings)) {
                if (!array_key_exists('enableClientEvents', $settings)) {
                    $settings['enableClientEvents'] = (bool)$settings['enableJsEvents'];
                }
                unset($settings['enableJsEvents']);
                $changed = true;
            }

            if (array_key_exists('jsGtmEventOptions', $settings)) {
                if (!array_key_exists('clientEventFields', $settings)) {
                    $legacy = $settings['jsGtmEventOptions'];
                    $settings['clientEventFields'] = is_array($legacy) ? $legacy : [];
                }
                unset($settings['jsGtmEventOptions']);
                $changed = true;
            }

            if ($changed) {
                $this->update($table, [
                    'settings' => Json::encode($settings),
                ], ['id' => $row['id']], [], false);
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        $table = Table::FORMIE_FIELD_LAYOUT_PAGES;

        foreach ((new Query())->select(['id', 'settings'])->from($table)->each() as $row) {
            $settings = Json::decodeIfJson($row['settings'] ?? '{}');

            if (!is_array($settings)) {
                continue;
            }

            $changed = false;

            if (array_key_exists('enableClientEvents', $settings)) {
                if (!array_key_exists('enableJsEvents', $settings)) {
                    $settings['enableJsEvents'] = (bool)$settings['enableClientEvents'];
                }
                unset($settings['enableClientEvents']);
                $changed = true;
            }

            if (array_key_exists('clientEventFields', $settings)) {
                if (!array_key_exists('jsGtmEventOptions', $settings)) {
                    $legacy = $settings['clientEventFields'];
                    $settings['jsGtmEventOptions'] = is_array($legacy) ? $legacy : [];
                }
                unset($settings['clientEventFields']);
                $changed = true;
            }

            if ($changed) {
                $this->update($table, [
                    'settings' => Json::encode($settings),
                ], ['id' => $row['id']], [], false);
            }
        }

        return true;
    }
}
