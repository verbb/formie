<?php
namespace verbb\formie\records;

use verbb\formie\helpers\Table;
use verbb\formie\Formie;
use Craft;

use craft\db\ActiveRecord;
use craft\records\Element;

use yii\db\ActiveQueryInterface;

class Submission extends ActiveRecord
{
    // Static Methods
    // =========================================================================

    public static function tableName(): string
    {
        return Table::FORMIE_SUBMISSIONS;
    }


    // Properties
    // =========================================================================

    private mixed $_previousContent = null;


    // Public Methods
    // =========================================================================

    public function transactions(): array
    {
        return [$this->getScenario() => self::OP_ALL];
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $references = Formie::$plugin->getUploadReferences();

        if ($references->isInstalled()) {
            $references->guard();

            if (!$insert) {
                $latest = Craft::$app->getDb()->createCommand('SELECT [[content]] FROM ' . Table::FORMIE_SUBMISSIONS . ' WHERE [[id]] = :id FOR UPDATE', [':id' => $this->id])->queryOne();
                $this->_previousContent = $latest ? $latest['content'] : null;
            }
        }

        return true;
    }

    public function afterSave($insert, $changedAttributes): void
    {
        if ($insert || array_key_exists('content', $changedAttributes) || array_key_exists('formId', $changedAttributes)) {
            Formie::$plugin->getUploadReferences()->reconcile((int)$this->id, true, $this->_previousContent);
        }

        parent::afterSave($insert, $changedAttributes);
    }

    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }
}
