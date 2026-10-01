<?php
namespace verbb\formie\conditions;

use verbb\formie\base\Field;
use verbb\formie\base\ParentFieldInterface;
use verbb\formie\base\RepeatableParentFieldInterface;
use verbb\formie\elements\Submission;
use verbb\formie\enums\SubmissionAuthorityType;
use verbb\formie\helpers\ConditionsHelper;
use verbb\formie\workflow\WorkflowContext;

final class ConditionVisibility
{
    // Static Methods
    // =========================================================================

    public static function followsConditions(Submission $submission): bool
    {
        $command = WorkflowContext::current()?->command;
        return !$command || $command->submission !== $submission || $command->authority->type === SubmissionAuthorityType::VISITOR || $command->clearConditionallyHiddenFields;
    }

    public static function rowScope(Field $field): array
    {
        $scope = [];
        $cursor = $field;

        while ($parent = $cursor->getParentField()) {
            if ($parent instanceof RepeatableParentFieldInterface && preg_match('/\[([0-9]+)\]$/', $cursor->getNamespace(), $match)) {
                $scope[(string)$parent->reference] = (int)$match[1];
            }
            $cursor = $parent;
        }
        return $scope;
    }

    public static function hidden(Field $field, Submission $submission): bool
    {
        if (!self::followsConditions($submission)) {
            return false;
        }

        if ($field->hasConditions()) {
            $settings = $field->conditions();
            $effect = $settings->effect;

            if (!in_array($effect, ['enable', 'disable'], true) && ConditionsHelper::evaluate($settings, $submission, rows: self::rowScope($field))->hides($effect)) {
                return true;
            }
        }

        if (($parent = $field->getParentField()) instanceof Field && self::hidden($parent, $submission)) {
            return true;
        }
        return $field->getPage()?->isConditionallyHidden($submission) ?? false;
    }

    public static function disabled(Field $field, Submission $submission): bool
    {
        if ($field->getIsDisabled()) {
            return true;
        }

        if (!self::followsConditions($submission)) {
            return false;
        }
        $settings = $field->conditions();
        $effect = $settings->effect;

        if ($field->hasConditions() && in_array($effect, ['enable', 'disable'], true) && ConditionsHelper::evaluate($settings, $submission, rows: self::rowScope($field))->hides($effect)) {
            return true;
        }
        return ($parent = $field->getParentField()) instanceof Field && self::disabled($parent, $submission);
    }

    public static function unavailable(Field $field, Submission $submission): bool
    {
        return self::disabled($field, $submission) || self::hidden($field, $submission);
    }


    // Public Methods
    // =========================================================================

    public function clear(Submission $submission): void
    {
        if (!self::followsConditions($submission)) {
            return;
        }
        $ordered = (new ConditionGraph())->orderedFields($submission->getForm());

        // Materialise row-bound field instances each pass: clearing a parent may remove rows.
        foreach ($ordered as $definition) {
            $walk = function(array $fields) use (&$walk, $definition, $submission): void {
                foreach ($fields as $field) {
                    if ($field->reference === $definition->reference && self::unavailable($field, $submission)) {
                        $submission->setFieldValue($field->valueKey(), null);
                        continue;
                    }

                    if ($field instanceof RepeatableParentFieldInterface) {
                        foreach ((array)$submission->getFieldValue($field->valueKey()) as $row => $value) {
                            $walk($field->getFields($row));
                        }
                    } elseif ($field instanceof ParentFieldInterface) {
                        $walk($field->getFields());
                    }
                }
            };
            $walk($submission->getFields());
        }
    }
}
