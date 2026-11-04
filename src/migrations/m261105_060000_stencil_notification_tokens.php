<?php
namespace verbb\formie\migrations;

use verbb\formie\base\ParentFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\helpers\References;
use verbb\formie\helpers\Table;
use verbb\formie\models\StencilData;

use Craft;
use craft\db\Query;
use craft\helpers\Json;
use craft\helpers\ProjectConfig;

use Throwable;

class m261105_060000_stencil_notification_tokens extends m261104_050000_bare_notification_tokens
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // Forms created from an unrepaired stencil after the earlier repair need the same recovery.
        parent::safeUp();

        foreach ((new Query())->select(['id', 'data'])->from(Table::FORMIE_STENCILS)->each(100, $this->db) as $row) {
            $data = Json::decodeIfJson($row['data']);

            if (!is_array($data)) {
                continue;
            }

            $updated = $this->_convertStencil($data, (string)$row['id']);

            if ($updated !== $data) {
                $this->update(Table::FORMIE_STENCILS, ['data' => Json::encode($updated)], ['id' => $row['id']], [], false);
            }
        }

        $projectConfig = Craft::$app->getProjectConfig();

        foreach ((array)$projectConfig->get('formie.stencils') as $uid => $config) {
            $data = ProjectConfig::unpackAssociativeArrays((array)($config['data'] ?? []));
            $updated = $this->_convertStencil($data, (string)$uid);

            if ($updated !== $data) {
                $projectConfig->set('formie.stencils.' . $uid . '.data', ProjectConfig::packAssociativeArrays($updated), 'Repair Formie stencil notification tokens');
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m261105_060000_stencil_notification_tokens cannot be reverted.\n";

        return false;
    }


    // Private Methods
    // =========================================================================

    private function _convertStencil(array $data, string $identifier): array
    {
        if (!is_array($data['notifications'] ?? null)) {
            return $data;
        }

        $form = null;
        $paths = [];

        foreach ($data['notifications'] as $key => $notification) {
            if (!is_array($notification)) {
                continue;
            }

            foreach (self::COLUMNS as $column) {
                $value = $notification[$column] ?? null;

                if (!is_string($value) || !preg_match(self::TOKEN_PATTERN, $value)) {
                    continue;
                }

                if ($form === null) {
                    try {
                        $form = new Form();
                        (new StencilData($data))->populateToForm($form, true);
                        $paths = $this->_referencePaths($form->getFields());
                    } catch (Throwable) {
                        // A broken, unused stencil must not prevent all other forms from upgrading.
                        Craft::warning("Skipped notification-token repair for stencil {$identifier}: its layout could not be loaded.", __METHOD__);

                        return $data;
                    }
                }

                $data['notifications'][$key][$column] = preg_replace_callback(self::TOKEN_PATTERN, function(array $match) use ($form, $paths): string {
                    $converted = $this->convertToken($form, $match);

                    // Stencils may not store field references yet. Keep validated handles portable across
                    // every materialization instead of persisting this temporary form's generated UUIDs.
                    return References::remapFieldReferenceToken($converted, $paths);
                }, $value);
            }
        }

        return $data;
    }

    private function _referencePaths(array $fields, string $prefix = ''): array
    {
        $paths = [];

        foreach ($fields as $field) {
            $path = $prefix . $field->handle;
            $paths[$field->reference] = $path;

            if ($field instanceof ParentFieldInterface) {
                $paths += $this->_referencePaths($field->getFields(), $path . '.');
            }
        }

        return $paths;
    }
}
