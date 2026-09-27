<?php
namespace verbb\formie\conditions;

use verbb\formie\elements\Submission;

final class ConditionSetEvaluator
{
    // Public Methods
    // =========================================================================

    public function evaluate(ConditionSet $set, Submission $submission, array $rows = []): ConditionEvaluation
    {
        if ($set->version !== ConditionOperator::schema()['version'] || !in_array($set->mode, ['all', 'any'], true) || !in_array($set->effect, ['show', 'hide', 'enable', 'disable'], true)) {
            return ConditionEvaluation::invalid('invalidSchema');
        }
        $results = [];
        $diagnostics = [];
        foreach ($set->rules as $index => $rule) {
            $result = (new ConditionRowEvaluator())->evaluate($rule, $submission, $rows);
            $results[] = $result->value;
            foreach ($result->diagnostics as $diagnostic) {
                $diagnostics[] = [...$diagnostic, 'rule' => $index];
            }
        }
        if ($diagnostics) {
            return new ConditionEvaluation(null, $diagnostics);
        }
        return new ConditionEvaluation($set->mode === 'all' ? !in_array(false, $results, true) : in_array(true, $results, true));
    }

    public function matchingRules(ConditionSet $set, Submission $submission): array
    {
        return array_values(array_filter($set->rules, static fn(ConditionRule $rule): bool => (new ConditionRowEvaluator())->evaluate($rule, $submission)->matches()));
    }
}
