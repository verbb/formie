<?php
namespace verbb\formie\migrations;

use verbb\formie\helpers\Table;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;
use craft\helpers\ProjectConfig as ProjectConfigHelper;

class m261104_020000_legacy_integration_types extends Migration
{
    // Constants
    // =========================================================================

    // Formie 3 kept these names working with class aliases. Saved connections still use them.
    private const TYPES = [
        'verbb\\formie\\integrations\\crm\\Freshdesk' => 'verbb\\formie\\integrations\\helpdesk\\Freshdesk',
        'verbb\\formie\\integrations\\miscellaneous\\Gorgias' => 'verbb\\formie\\integrations\\helpdesk\\Gorgias',
        'verbb\\formie\\integrations\\miscellaneous\\Zendesk' => 'verbb\\formie\\integrations\\helpdesk\\Zendesk',
        'verbb\\formie\\integrations\\miscellaneous\\Slack' => 'verbb\\formie\\integrations\\messaging\\Slack',
        'verbb\\formie\\integrations\\miscellaneous\\Telegram' => 'verbb\\formie\\integrations\\messaging\\Telegram',
        'verbb\\formie\\integrations\\webhooks\\Webhook' => 'verbb\\formie\\integrations\\automations\\WebRequest',
        'verbb\\formie\\integrations\\automations\\Webhook' => 'verbb\\formie\\integrations\\automations\\WebRequest',
        'verbb\\formie\\integrations\\webhooks\\Zapier' => 'verbb\\formie\\integrations\\automations\\Zapier',
    ];

    private const WEB_REQUEST = 'verbb\\formie\\integrations\\automations\\WebRequest';
    private const CALENDAR_EVENT = 'verbb\\formie\\integrations\\elements\\CalendarEvent';


    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // Rows are updated directly: every integration is instantiated when any one of them
        // loads, so a single unconverted connection makes all of them unavailable.
        $webRequestHandles = [];

        foreach ((new Query())->select(['id', 'handle', 'type', 'settings'])->from(Table::FORMIE_INTEGRATIONS)->all($this->db) as $row) {
            $type = self::TYPES[$row['type']] ?? $row['type'];
            $settings = Json::decodeIfJson($row['settings'] ?? '');
            $normalized = is_array($settings) ? $this->_normalizeSettings($type, $settings) : $settings;

            if ($type === self::WEB_REQUEST) {
                $webRequestHandles[] = $row['handle'];
            }

            if ($type !== $row['type'] || $normalized !== $settings) {
                $this->update(Table::FORMIE_INTEGRATIONS, [
                    'type' => $type,
                    'settings' => is_array($normalized) ? Json::encode($normalized) : $row['settings'],
                ], ['id' => $row['id']], updateTimestamp: false);
            }
        }

        $this->_migrateFormBindings($webRequestHandles);
        $this->_migrateProjectConfig($webRequestHandles);

        return true;
    }

    public function safeDown(): bool
    {
        return false;
    }


    // Private Methods
    // =========================================================================

    private function _normalizeSettings(string $type, array $settings): array
    {
        // Form-level site mapping replaced this connection setting, which no longer exists.
        if ($type === self::CALENDAR_EVENT) {
            unset($settings['siteId']);
        }

        if ($type === self::WEB_REQUEST) {
            $settings = $this->_normalizeWebhook($settings);
        }

        return $settings;
    }

    private function _normalizeWebhook(array $settings): array
    {
        if (!array_key_exists('webhook', $settings)) {
            return $settings;
        }

        if ($settings['webhook'] && empty($settings['url'])) {
            $settings['url'] = $settings['webhook'];
        }

        unset($settings['webhook']);

        return $settings;
    }

    private function _migrateFormBindings(array $handles): void
    {
        if (!$handles) {
            return;
        }

        foreach ([Table::FORMIE_FORMS => 'settings', Table::FORMIE_STENCILS => 'data'] as $table => $column) {
            foreach ((new Query())->select(['id', $column])->from($table)->each(100, $this->db) as $row) {
                $data = $row[$column];

                // Older stencils may have been JSON-encoded twice.
                for ($layer = 0; $layer < 2 && is_string($data); $layer++) {
                    $data = Json::decodeIfJson($data);
                }

                if (!is_array($data)) {
                    continue;
                }

                $clean = $column === 'settings' ? $this->_normalizeBindings($data, $handles) : $this->_normalizeStencil($data, $handles);

                if ($clean !== $data) {
                    $this->update($table, [$column => Json::encode($clean)], ['id' => $row['id']], updateTimestamp: false);
                }
            }
        }
    }

    private function _normalizeStencil(array $data, array $handles): array
    {
        if (isset($data['settings']) && is_array($data['settings'])) {
            $data['settings'] = $this->_normalizeBindings($data['settings'], $handles);
        }

        return $data;
    }

    private function _normalizeBindings(array $settings, array $handles): array
    {
        foreach ($handles as $handle) {
            if (isset($settings['integrations'][$handle]) && is_array($settings['integrations'][$handle])) {
                $settings['integrations'][$handle] = $this->_normalizeWebhook($settings['integrations'][$handle]);
            }
        }

        return $settings;
    }

    private function _migrateProjectConfig(array $handles): void
    {
        if (!Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            return;
        }

        $projectConfig = Craft::$app->getProjectConfig();
        $muted = $projectConfig->muteEvents;

        // The database rows are already correct, and applying a connection's config would
        // also clear its cached provider data and connection state.
        $projectConfig->muteEvents = true;

        try {
            foreach ($projectConfig->get('formie.integrations', true) ?? [] as $uid => $config) {
                $type = self::TYPES[$config['type'] ?? ''] ?? ($config['type'] ?? '');
                $settings = ProjectConfigHelper::unpackAssociativeArrays($config['settings'] ?? []);
                $normalized = $this->_normalizeSettings($type, $settings);

                if ($type !== ($config['type'] ?? '') || $normalized !== $settings) {
                    $config['type'] = $type;
                    $config['settings'] = ProjectConfigHelper::packAssociativeArrays($normalized);
                    $projectConfig->set("formie.integrations.{$uid}", $config, 'Update legacy Formie integration settings');
                }
            }

            if ($handles) {
                foreach ($projectConfig->get('formie.stencils', true) ?? [] as $uid => $config) {
                    $data = ProjectConfigHelper::unpackAssociativeArrays($config['data'] ?? []);
                    $normalized = $this->_normalizeStencil($data, $handles);

                    if ($normalized !== $data) {
                        $config['data'] = ProjectConfigHelper::packAssociativeArrays($normalized);
                        $projectConfig->set("formie.stencils.{$uid}", $config, 'Update legacy Formie stencil integration settings');
                    }
                }
            }
        } finally {
            $projectConfig->muteEvents = $muted;
        }
    }
}
