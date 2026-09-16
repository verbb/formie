<?php
namespace verbb\formie\migrations;

use verbb\formie\elements\Form;
use verbb\formie\helpers\Table;

use craft\db\Migration;
use craft\db\Query;

class m260913_000000_notification_field_references extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        foreach ((new Query())->from(Table::FORMIE_NOTIFICATIONS)->each() as $row) {
            $form = Form::find()->id($row['formId'])->status(null)->one();

            if (!$form) {
                continue;
            }

            $changes = [];

            foreach ($row as $column => $value) {
                if (!is_string($value) || !str_contains($value, '{field:')) {
                    continue;
                }

                // Formie 3 stored handles in notification tokens. Replace only handles
                // belonging to this form, leaving current references and unknown tokens intact.
                $updated = preg_replace_callback('/\{field:([^}:;|]+)([^}]*)\}/', static function(array $match) use ($form): string {
                    $field = $form->getFieldByHandle($match[1]);

                    return $field && $field->reference
                        ? '{field:' . $field->reference . $match[2] . '}'
                        : $match[0];
                }, $value);

                if ($updated !== $value) {
                    $changes[$column] = $updated;
                }
            }

            if ($changes) {
                $this->update(Table::FORMIE_NOTIFICATIONS, $changes, ['id' => $row['id']], [], false);
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m260913_000000_notification_field_references cannot be reverted.\n";

        return false;
    }
}
