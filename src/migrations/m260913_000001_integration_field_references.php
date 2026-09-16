<?php
namespace verbb\formie\migrations;

use verbb\formie\elements\Form;
use verbb\formie\helpers\Table;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m260913_000001_integration_field_references extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        foreach ((new Query())->select(['id', 'settings'])->from(Table::FORMIE_FORMS)->each() as $row) {
            $settings = Json::decode($row['settings']) ?: [];

            if (empty($settings['integrations'])) {
                continue;
            }

            $form = Form::find()->id($row['id'])->status(null)->one();

            if (!$form) {
                continue;
            }

            $original = Json::encode($settings['integrations']);
            $updated = preg_replace_callback('/\{field:([^}:;|]+)([^}]*)\}/', static function(array $match) use ($form): string {
                $field = $form->getFieldByHandle($match[1]);

                return $field && $field->reference
                    ? '{field:' . $field->reference . $match[2] . '}'
                    : $match[0];
            }, $original);

            if ($updated !== $original) {
                $settings['integrations'] = Json::decode($updated);
                $this->update(Table::FORMIE_FORMS, ['settings' => Json::encode($settings)], ['id' => $row['id']], [], false);
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m260913_000001_integration_field_references cannot be reverted.\n";

        return false;
    }
}
