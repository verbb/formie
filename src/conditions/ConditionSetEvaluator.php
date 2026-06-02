<?php
namespace verbb\formie\conditions;

use verbb\formie\elements\Submission;
use verbb\formie\helpers\ArrayHelper;

use Throwable;

class ConditionSetEvaluator
{
    // Properties
    // =========================================================================

    private ConditionRowEvaluator $rowEvaluator;


    // Public Methods
    // =========================================================================
    
    public function __construct(ConditionRowEvaluator $rowEvaluator)
    {
        $this->rowEvaluator = $rowEvaluator;
    }

    public function evaluateRows(array $conditions, Submission $submission, ?callable $callback = null): array
    {
        $results = [];

        foreach ($conditions as $condition) {
            $variables = [
                'field' => $condition['field'] ?? '',
                'value' => $condition['value'] ?? '',
            ];

            if (!trim(ArrayHelper::recursiveImplode($variables, ''))) {
                continue;
            }

            try {
                $result = $this->rowEvaluator->evaluate($condition, $submission);
            } catch (Throwable) {
                // Treat malformed rows as non-matches and keep evaluating the
                // rest so stale builder data degrades gracefully.
                continue;
            }

            if ($callback) {
                $callbackResult = $callback($result, $condition);

                if ($callbackResult) {
                    $results[] = $callbackResult;
                }
            } else {
                $results[] = $result;
            }
        }

        return $results;
    }

    public function matches(array $conditionSettings, Submission $submission): bool
    {
        $conditions = $conditionSettings['conditions'] ?? [];
        $conditionRule = (string)($conditionSettings['conditionRule'] ?? 'all');

        $results = $this->evaluateRows($conditions, $submission);

        if ($conditionRule === 'all') {
            return (bool)array_product($results);
        }

        return in_array(true, $results, true);
    }
}
