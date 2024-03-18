<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\fields;
use verbb\formie\fields\SingleLineText;
use verbb\formie\fields\subfields;
use verbb\formie\models\FieldLayout;
use verbb\formie\positions\Hidden as HiddenPosition;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\Table;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

use Throwable;

class m240318_000000_notification_fields extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $notifications = (new Query())
            ->select(['*'])
            ->from([Table::FORMIE_NOTIFICATIONS])
            ->all();

        // Change all `{field.address}` to `{field:address}`
        foreach ($notifications as $notification) {
            $hasChanged = false;

            foreach ($notification as $prop => $value) {
                if (is_string($value) && str_contains($value, '{field.')) {
                    $hasChanged = true;

                    $notification[$prop] = str_replace('{field.', '{field:', $value);
                }
            }

            if ($hasChanged) {
                $this->update(Table::FORMIE_NOTIFICATIONS, $notification, ['id' => $notification['id']], [], false);
            }
        }

        $stencils = (new Query())
            ->select(['*'])
            ->from([Table::FORMIE_STENCILS])
            ->all();

        foreach ($stencils as $stencil) {
            $data = $stencil['data'] ?? '';
            $data = str_replace('{field.', '{field:', $data);

            $this->update(Table::FORMIE_STENCILS, [
                'data' => $data,
            ], ['id' => $stencil['id']], [], false);
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m240318_000000_notification_fields cannot be reverted.\n";

        return false;
    }

}
