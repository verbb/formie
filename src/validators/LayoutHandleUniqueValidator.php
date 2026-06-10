<?php
namespace verbb\formie\validators;

use verbb\formie\helpers\Table;
use verbb\formie\models\FieldLayout;

use Craft;
use craft\db\Query;

use yii\validators\Validator;

class LayoutHandleUniqueValidator extends Validator
{
    private static array $_ignoredFieldIdsByLayout = [];
    private static array $_assignedHandlesByLayout = [];

    public static function beginLayoutSaveScope(FieldLayout $layout): void
    {
        $layoutId = $layout->id;

        if (!$layoutId) {
            return;
        }

        self::$_ignoredFieldIdsByLayout[$layoutId] = self::_getDeletedLayoutFieldIds($layout);
        self::$_assignedHandlesByLayout[$layoutId] = [];
    }

    public static function endLayoutSaveScope(?int $layoutId = null): void
    {
        if ($layoutId === null) {
            self::$_ignoredFieldIdsByLayout = [];
            self::$_assignedHandlesByLayout = [];
            return;
        }

        unset(self::$_ignoredFieldIdsByLayout[$layoutId], self::$_assignedHandlesByLayout[$layoutId]);
    }

    public static function registerAssignedHandle(?int $layoutId, ?string $handle): void
    {
        if (!$layoutId || !$handle) {
            return;
        }

        self::$_assignedHandlesByLayout[$layoutId][] = strtolower($handle);
    }

    public function validateAttribute($model, $attribute): void
    {
        $layoutId = $model->layoutId ?? null;
        $handle = $model->$attribute ?? null;
        $fieldId = $model->id ?? null;

        if (!$layoutId || !$handle || $model->hasErrors($attribute)) {
            return;
        }

        $normalizedHandle = strtolower($handle);
        $assignedHandles = self::$_assignedHandlesByLayout[$layoutId] ?? [];

        if (in_array($normalizedHandle, $assignedHandles, true)) {
            $message = $this->message ?: Craft::t('yii', '{attribute} "{value}" has already been taken.', [
                'attribute' => $model->getAttributeLabel($attribute),
                'value' => $handle,
            ]);

            $this->addError($model, $attribute, $message);

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

        $ignoredFieldIds = self::$_ignoredFieldIdsByLayout[$layoutId] ?? [];
        if ($ignoredFieldIds) {
            $query->andWhere(['not in', 'ff.id', $ignoredFieldIds]);
        }

        if ($query->exists()) {
            $message = $this->message ?: Craft::t('yii', '{attribute} "{value}" has already been taken.', [
                'attribute' => $model->getAttributeLabel($attribute),
                'value' => $handle,
            ]);

            $this->addError($model, $attribute, $message);
        }
    }

    private static function _getDeletedLayoutFieldIds(FieldLayout $layout): array
    {
        $layoutId = $layout->id;

        if (!$layoutId) {
            return [];
        }

        $existingFieldIds = (new Query())
            ->select(['id'])
            ->from(Table::FORMIE_FORM_FIELDS)
            ->where(['layoutId' => $layoutId])
            ->column();

        if (!$existingFieldIds) {
            return [];
        }

        $keptFieldIds = [];
        self::_collectFieldIdsFromLayout($layout, $keptFieldIds);

        return array_values(array_filter(array_diff($existingFieldIds, $keptFieldIds)));
    }

    private static function _collectFieldIdsFromLayout(FieldLayout $layout, array &$keptFieldIds): void
    {
        foreach ($layout->getPages() as $page) {
            foreach ($page->getRows() as $row) {
                self::_collectFieldIdsFromFields($row->getFields(), $keptFieldIds);
            }
        }
    }

    private static function _collectFieldIdsFromFields(array $fields, array &$keptFieldIds): void
    {
        foreach ($fields as $field) {
            if ($field?->id) {
                $keptFieldIds[] = (int)$field->id;
            }

            if (!method_exists($field, 'getRows')) {
                continue;
            }

            $nestedRows = $field->getRows();
            if (!is_array($nestedRows)) {
                continue;
            }

            foreach ($nestedRows as $nestedRow) {
                self::_collectFieldIdsFromFields($nestedRow->getFields(), $keptFieldIds);
            }
        }
    }
}
