<?php
namespace verbb\formie\validators;

use verbb\formie\base\Field;
use verbb\formie\helpers\Table;

use Craft;
use craft\db\Query;

use yii\validators\Validator;

class LayoutHandleUniqueValidator extends Validator
{
    // Public Methods
    // =========================================================================

    public function validateAttribute($model, $attribute): void
    {
        $layoutId = $model->layoutId ?? null;
        $handle = $model->$attribute ?? null;
        $fieldId = $model->id ?? null;

        if (!$handle || $model->hasErrors($attribute)) {
            return;
        }

        if ($model instanceof Field && $model->layoutSaveContext) {
            $isUnique = $model->layoutSaveContext->getIsHandleUnique($model);

            if ($isUnique !== null) {
                if (!$isUnique) {
                    $this->_addDuplicateError($model, $attribute, $handle);
                }

                return;
            }
        }

        if (!$layoutId) {
            return;
        }

        $query = (new Query())
            ->from(['ff' => Table::FORMIE_FORM_FIELDS])
            ->innerJoin(['f' => Table::FORMIE_FIELDS], '[[f.id]] = [[ff.fieldId]]')
            ->where([
                'ff.layoutId' => $layoutId,
                'f.handle' => $handle,
            ]);

        if ($fieldId) {
            $query->andWhere(['not', ['ff.id' => $fieldId]]);
        }

        if ($query->exists()) {
            $this->_addDuplicateError($model, $attribute, $handle);
        }
    }


    // Private Methods
    // =========================================================================

    private function _addDuplicateError($model, string $attribute, string $handle): void
    {
        $message = $this->message ?: Craft::t('yii', '{attribute} "{value}" has already been taken.', [
            'attribute' => $model->getAttributeLabel($attribute),
            'value' => $handle,
        ]);

        $this->addError($model, $attribute, $message);
    }
}
