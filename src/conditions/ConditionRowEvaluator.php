<?php
namespace verbb\formie\conditions;

use verbb\formie\elements\Submission;
use verbb\formie\fields\Date;
use verbb\formie\helpers\References;
use verbb\formie\references\ReferenceContext;
use verbb\formie\references\ReferenceUsage;

use Throwable;

final class ConditionRowEvaluator
{
    // Public Methods
    // =========================================================================

    public function evaluate(ConditionRule $rule, Submission $submission, array $rows = []): ConditionEvaluation
    {
        if ($rule->reference === '') {
            return ConditionEvaluation::invalid('missingReference');
        }
        try {
            $token = str_starts_with($rule->reference, '{') ? $rule->reference : References::field($rule->reference);
            $result = References::resolveValue($token, ReferenceContext::forSubmission($submission, rows: $rows, usage: ReferenceUsage::Condition));
            $value = $result->requireValue();
            $type = is_bool($value) ? 'boolean' : (is_int($value) || is_float($value) ? 'number' : (is_array($value) ? 'collection' : 'text'));
            if ($result->field && $result->fieldProjection === 'value' && $result->expression->transformerId === '') {
                $value = $result->field->getValueForCondition($value, $submission);
                $type = ConditionCompiler::fieldType($result->field);
            }
            if ($result->field instanceof Date && in_array($result->expression->selector, ['date', 'time'], true)) {
                $type = $result->expression->selector;
            }
            if ($result->field && $result->fieldProjection === 'collection' && is_array($value)) {
                $value = array_map(fn($item) => $result->field->getValueForCondition($item, $submission), $value);
                $type = 'collection';
            }
            if (($result->expression->transformerParams['scope'] ?? '') === 'rows' && $result->fieldProjection === 'value') {
                $value = [$value];
                $type = 'collection';
            }
            return ConditionOperator::evaluate($rule->operator, $value, $rule->value, $type);
        } catch (Throwable) {
            return ConditionEvaluation::invalid('unresolvedReference');
        }
    }
}
