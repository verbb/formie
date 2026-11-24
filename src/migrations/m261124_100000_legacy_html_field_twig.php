<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;
use verbb\formie\fields\Html;
use verbb\formie\helpers\FieldBuilderPolicy;
use verbb\formie\helpers\Table;
use verbb\formie\services\Stencils as StencilsService;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;
use craft\helpers\ProjectConfig;

class m261124_100000_legacy_html_field_twig extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // HTML fields always rendered their content as Twig before they had an Allow Twig setting, so fields
        // saved before then have no `allowTwig` key. Turn it on for those whose content uses Twig, so they keep
        // rendering it instead of showing the Twig source. Fields saved since always have the key.
        // With Twig turned off for HTML fields, a field with Allow Twig on renders nothing and stops its form from saving.
        if (!FieldBuilderPolicy::allowHtmlFieldTwig()) {
            return true;
        }

        $this->_updateFields();
        $this->_updateStencils();

        return true;
    }

    public function safeDown(): bool
    {
        echo "m261124_100000_legacy_html_field_twig cannot be reverted.\n";

        return false;
    }


    // Private Methods
    // =========================================================================

    private function _updateFields(): void
    {
        $fields = (new Query())
            ->select(['id', 'settings'])
            ->from(Table::FORMIE_FIELDS)
            ->where(['type' => Html::class])
            ->all($this->db);

        foreach ($fields as $field) {
            $settings = Json::decodeIfJson((string)$field['settings']);

            if (!is_array($settings) || array_key_exists('allowTwig', $settings)) {
                continue;
            }

            // Translated content in a site override renders through the same setting.
            $contents = [$settings['htmlContent'] ?? null];

            $overrides = (new Query())
                ->select(['overrides'])
                ->from(Table::FORMIE_FIELD_SITE_OVERRIDES)
                ->where(['fieldId' => $field['id']])
                ->column($this->db);

            foreach ($overrides as $override) {
                $override = Json::decodeIfJson((string)$override);
                $contents[] = is_array($override) ? ($override['htmlContent'] ?? null) : null;
            }

            if (!array_filter($contents, $this->_usesTwig(...))) {
                continue;
            }

            $settings['allowTwig'] = true;
            $this->update(Table::FORMIE_FIELDS, ['settings' => Json::encode($settings)], ['id' => $field['id']], [], false);
        }
    }

    private function _updateStencils(): void
    {
        $stencils = (new Query())->select(['data', 'uid'])->from(Table::FORMIE_STENCILS)->indexBy('uid')->column($this->db);
        $updated = [];

        foreach ($stencils as $uid => $data) {
            $decoded = Json::decodeIfJson((string)$data);

            if (!is_array($decoded)) {
                continue;
            }

            $changed = false;
            $decoded = $this->_updateFieldConfigs($decoded, $changed);

            if ($changed) {
                $updated[$uid] = $decoded;
                $this->update(Table::FORMIE_STENCILS, ['data' => Json::encode($decoded)], ['uid' => $uid], [], false);
            }
        }

        $projectConfig = Craft::$app->getProjectConfig();

        // Config that has already reached this version carries the updated data and is applied as it is.
        if (!$updated || $projectConfig->readOnly || version_compare($projectConfig->get('plugins.formie.schemaVersion', true) ?? '0', Formie::$plugin->schemaVersion, '>=')) {
            return;
        }

        foreach ($updated as $uid => $data) {
            $path = StencilsService::CONFIG_STENCILS_KEY . '.' . $uid;

            if ($config = $projectConfig->get($path)) {
                $config = ProjectConfig::unpackAssociativeArray((array)$config);
                $projectConfig->set($path, ProjectConfig::packAssociativeArray(['data' => $data] + $config), 'Keep Twig in HTML fields from earlier Formie versions');
            }
        }
    }

    private function _updateFieldConfigs(array $data, bool &$changed): array
    {
        // Stencils keep fields as `type` and `settings`, with nested fields inside Group and Repeater settings.
        if (($data['type'] ?? null) === Html::class && is_array($data['settings'] ?? null)) {
            $settings = $data['settings'];

            if (!array_key_exists('allowTwig', $settings) && $this->_usesTwig($settings['htmlContent'] ?? null)) {
                $data['settings']['allowTwig'] = true;
                $changed = true;
            }
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->_updateFieldConfigs($value, $changed);
            }
        }

        return $data;
    }

    private function _usesTwig(mixed $content): bool
    {
        return is_string($content) && preg_match('/\{[{%#]/', $content) === 1;
    }
}
