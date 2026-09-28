<?php
namespace verbb\formie\validators;

use verbb\formie\base\Field;
use verbb\formie\helpers\Table;

use Craft;
use craft\db\Query;

use yii\validators\Validator;

class FieldReferenceUniqueValidator extends Validator
{
    // Public Methods
    // =========================================================================

    public function validateAttribute($model, $attribute): void
    {
        $reference = $model->$attribute ?? null;

        if (!$reference || $model->hasErrors($attribute)) {
            return;
        }

        if ($model instanceof Field && $model->layoutSaveContext) {
            $isUnique = $model->layoutSaveContext->getIsReferenceUnique($model);

            if ($isUnique !== null) {
                if (!$isUnique) {
                    $this->_addDuplicateError($model, $attribute, $reference);
                }

                return;
            }
        }

        $query = (new Query())
            ->from(Table::FORMIE_FORM_FIELDS)
            ->where(['reference' => $reference]);

        if ($model->id ?? null) {
            $query->andWhere(['not', ['id' => $model->id]]);
        }

        if ($query->exists()) {
            $this->_addDuplicateError($model, $attribute, $reference);
        }
    }


    // Private Methods
    // =========================================================================

    private function _addDuplicateError($model, string $attribute, string $reference): void
    {
        $message = $this->message ?: Craft::t('yii', '{attribute} "{value}" has already been taken.', [
            'attribute' => $model->getAttributeLabel($attribute),
            'value' => $reference,
        ]);

        $this->addError($model, $attribute, $message);
    }
}
