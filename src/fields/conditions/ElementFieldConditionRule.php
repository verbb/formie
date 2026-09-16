<?php
namespace verbb\formie\fields\conditions;

use verbb\formie\base\ElementFieldInterface;

use Craft;
use craft\base\conditions\BaseElementSelectConditionRule;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\ElementQueryInterface;

use yii\db\Expression;
use yii\db\QueryInterface;

class ElementFieldConditionRule extends BaseElementSelectConditionRule implements FieldConditionRuleInterface
{
    // Traits
    // =========================================================================

    use FieldConditionRuleTrait;


    // Properties
    // =========================================================================

    public string $operator = self::OPERATOR_NOT_EMPTY;

    protected bool $reloadOnOperatorChange = true;


    // Public Methods
    // =========================================================================

    public function modifyQuery(QueryInterface $query): void
    {
        $field = $this->field();

        if (!$field instanceof ElementFieldInterface || ($column = $field->getValueSql()) === null) {
            return;
        }

        // Match the resolved field value, including disabled targets but excluding deleted ones.
        // Stored IDs alone are not evidence that a relation is still present.
        $db = Craft::$app->getDb();
        $contains = $db->getIsPgsql()
            ? "CAST($column AS jsonb) @> jsonb_build_array([[elements.id]])"
            : "JSON_CONTAINS($column, CAST([[elements.id]] AS CHAR))";
        $related = $field::elementType()::find()->site('*')->unique()->status(null)->select(['elements.id']);
        // Keep the correlation outside Craft's derived table for database portability.
        $relatedQuery = $related->prepare($db->getQueryBuilder());
        $relatedQuery->andWhere(new Expression($contains));

        $query->andWhere([$this->elementQueryParam() === ':empty:' ? 'not exists' : 'exists', $relatedQuery]);
    }


    // Protected Methods
    // =========================================================================

    protected function elementType(): string
    {
        $field = $this->field();

        return $field::elementType();
    }

    protected function sources(): ?array
    {
        $field = $this->field();

        return (array)$field->getInputSources();
    }

    protected function selectionCondition(): ?ElementConditionInterface
    {
        $field = $this->field();

        return $field->getSelectionCondition();
    }

    protected function criteria(): ?array
    {
        $field = $this->field();

        return $field->getInputSelectionCriteria();
    }

    protected function allowMultiple(): bool
    {
        return true;
    }

    protected function operators(): array
    {
        return array_filter([
            self::OPERATOR_NOT_EMPTY,
            self::OPERATOR_EMPTY,
        ]);
    }

    protected function elementQueryParam(): string
    {
        return $this->operator === self::OPERATOR_EMPTY ? ':empty:' : ':notempty:';
    }

    protected function matchFieldValue($value): bool
    {
        if (!$this->field() instanceof ElementFieldInterface) {
            return true;
        }

        if ($value instanceof ElementQueryInterface) {
            // Ignore the related elements’ statuses and target site
            // so conditions reflect what authors see in the UI
            $value = (clone $value)->site('*')->unique()->status(null);
        }

        if ($value instanceof ElementQueryInterface) {
            $isEmpty = !$value->exists();
        } else {
            $isEmpty = $value->isEmpty();
        }

        if ($this->operator === self::OPERATOR_EMPTY) {
            return $isEmpty;
        }

        return !$isEmpty;
    }
}
