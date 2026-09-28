<?php
namespace verbb\formie\conditions;

use verbb\formie\base\Field;
use verbb\formie\base\OptionsField;
use verbb\formie\base\ParentFieldInterface;
use verbb\formie\base\RepeatableParentFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\fields\Date;
use verbb\formie\fields\definitions\FieldReferenceValue;
use verbb\formie\fields\Recipients;
use verbb\formie\helpers\References;
use verbb\formie\references\FieldReferenceResolver;
use verbb\formie\references\ReferenceCatalogue;
use verbb\formie\references\ReferenceContext;
use verbb\formie\references\ReferenceType;

use Throwable;

/** Both markup products receive this versioned wire contract. */
final class ConditionCompiler
{
    // Static Methods
    // =========================================================================

    public static function fieldType(Field $field): string
    {
        if ($field instanceof OptionsField) {
            return $field->multi ? 'collection' : 'text';
        }
        if ($field instanceof Recipients) {
            return in_array($field->displayType, ['checkboxes', 'multi-select'], true) ? 'collection' : 'text';
        }
        if ($field instanceof Date) {
            return $field->getCollectsRange() ? 'collection' : ($field->getIsTime() ? 'time' : ($field->getIsDate() ? 'date' : 'datetime'));
        }
        return match ($field->valueType()->kind) {
            'number' => 'number',
            'boolean' => 'boolean',
            'array', 'relationQuery' => 'collection',
            'object' => in_array($field->fieldKind(), ['options', 'recipients'], true) ? 'text' : 'collection',
            default => 'text',
        };
    }


    // Public Methods
    // =========================================================================

    public function compile(ConditionSet $set, ?Form $form = null): array
    {
        $rules = [];
        foreach ($set->rules as $rule) {
            $token = str_starts_with($rule->reference, '{') ? $rule->reference : References::field($rule->reference);
            $expression = References::parseReferenceExpression($token);
            $field = null;
            $path = '';
            $domPath = '';
            try {
                if ($form && $expression->target === 'field') {
                    $entry = (new FieldReferenceResolver())->findField($expression->identifier, new ReferenceContext(form: $form));
                    $field = $entry['field'] ?? null;
                    $path = $entry['path'] ?? '';
                    $domPath = implode('.', array_map(static fn($part): string => is_array($part) ? '__ROW__' : $part, $entry['parts'] ?? []));
                }
            } catch (Throwable) {
                // Keep the unresolved operand in the wire model so it cannot become an empty matching set.
            }
            $browser = $expression->isValid && ($expression->target === 'field' && $field !== null);
            if ($expression->transformerId !== '') {
                $browser = $browser && ((new ReferenceCatalogue())->transform($expression->transformerId)?->browser ?? false);
            }
            $type = $field instanceof Field ? self::fieldType($field) : ($expression->identifier === 'id' ? 'number' : 'text');
            if ($expression->selector !== '' && $field) {
                $type = $field instanceof Date && in_array($expression->selector, ['date', 'time'], true) ? $expression->selector : 'text';
                if ($field instanceof OptionsField && $field->multi) {
                    $type = 'collection';
                }
                $selectorAvailable = false;
                foreach ($field->referenceValues() as $referenceValue) {
                    if ($referenceValue->matchesSelector($expression->selector)) {
                        $selectorAvailable = $referenceValue->supportsBrowser && $referenceValue->appliesTo($field);
                        $type = self::_referenceValueType($referenceValue, $type);
                    }
                }
                $browser = $browser && $selectorAvailable;
            }
            if (in_array($expression->transformerParams['scope'] ?? '', ['all', 'rows'], true)) {
                $type = 'collection';
            } elseif (($expression->transformerParams['scope'] ?? '') === 'count') {
                $type = 'number';
            }
            $selector = $expression->selector;
            $params = $expression->transformerParams;
            if ($field instanceof RepeatableParentFieldInterface && $selector !== '' && !isset($params['scope'])) {
                if (preg_match('/^([0-9]+):(.*)$/', $selector, $match)) {
                    $params['scope'] = 'index';
                    $params['index'] = $match[1];
                    $selector = $match[2];
                } else {
                    $browser = false;
                }
            }
            $source = [
                'raw' => $token, 'target' => $expression->target, 'handle' => $path ?: $expression->identifier, 'domHandle' => $domPath ?: $expression->identifier,
                'selector' => $selector, 'defaultValue' => $expression->default,
                'transformerId' => $expression->transformerId, 'transformerParams' => $params,
                'isValid' => $expression->isValid, 'browserSafe' => $browser,
            ];
            $rules[] = [
                'fieldId' => $field ? (string)$field->id : $expression->identifier,
                'field' => $token, 'source' => $source, 'operator' => $rule->operator,
                'value' => $rule->value, 'valueType' => $type, 'browserSafe' => $browser,
            ];
        }
        return ['version' => $set->version, 'purpose' => $set->purpose, 'mode' => $set->mode, 'effect' => $set->effect, 'clearOnHide' => true, 'rules' => $rules];
    }


    // Private Methods
    // =========================================================================

    private static function _referenceValueType(FieldReferenceValue $referenceValue, string $fallback): string
    {
        if (in_array(ReferenceType::List, $referenceValue->types, true)) {
            return 'collection';
        }

        if (in_array(ReferenceType::Number, $referenceValue->types, true)) {
            return 'number';
        }

        if (in_array(ReferenceType::Boolean, $referenceValue->types, true)) {
            return 'boolean';
        }

        if (in_array(ReferenceType::Date, $referenceValue->types, true)) {
            return 'date';
        }

        return $fallback;
    }
}
