<?php
namespace verbb\formie\fields\conditions;

use verbb\formie\base\ElementFieldInterface;

use Craft;
use craft\base\conditions\BaseElementSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\ElementCollection;
use craft\helpers\Json;

use yii\base\InvalidConfigException;
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
        $values = $this->elementQueryParam();

        if ($values !== null) {
            $jsonPath = [$field->uid];

            $db = Craft::$app->getDb();
            $qb = $db->getQueryBuilder();
            $column = $qb->jsonExtract('formie_submissions.content', $jsonPath);

            // Add our own query handling to allow parial matches for multi-array values
            // Probably look at refactoring this back to `ElementField::queryCondition()`
            foreach ($values as $i => $value) {
                $query->andWhere(new Expression("JSON_CONTAINS($column, :val$i, '$')", [":val$i" => Json::encode($value)]));
            }
        }
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

    protected function elementQueryParam(): array|null
    {
        return $this->getElementIds();
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
