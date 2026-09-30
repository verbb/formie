<?php
namespace verbb\formie\conditions;

use verbb\formie\elements\Submission;
use verbb\formie\helpers\References;

/** Resolved predicates for one submission content snapshot, never persisted or shared between submissions. */
final class ConditionState
{
    // Properties
    // =========================================================================

    private array $_evaluations = [];


    // Public Methods
    // =========================================================================

    public function invalidate(): void
    {
        $this->_evaluations = [];
    }

    public function evaluate(ConditionSet $set, Submission $submission, array $rows = []): ConditionEvaluation
    {
        // Server-only sources and transforms may depend on mutable external context.
        // Share field predicates; do not freeze arbitrary third-party callbacks.
        foreach ($set->rules as $rule) {
            $token = str_starts_with($rule->reference, '{') ? $rule->reference : References::field($rule->reference);
            $expression = References::parseReferenceExpression($token);
            if (!$expression->isValid || $expression->target !== 'field' || $expression->transformerId !== '') {
                return (new ConditionSetEvaluator())->evaluate($set, $submission, $rows);
            }
        }

        ksort($rows);
        $key = hash('sha256', serialize([$set, $rows]));
        return $this->_evaluations[$key] ??= (new ConditionSetEvaluator())->evaluate($set, $submission, $rows);
    }
}
