<?php
namespace verbb\formie\conditions;

use verbb\formie\base\ParentFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\helpers\References;
use verbb\formie\references\FieldReferenceResolver;
use verbb\formie\references\ReferenceContext;

use RuntimeException;

/** Dependency order supports acyclic legacy forward references without iterative clearing. */
final class ConditionGraph
{
    // Public Methods
    // =========================================================================

    public function orderedFields(Form $form): array
    {
        $fields = [];
        $edges = [];
        $index = function(array $items, ?string $parent = null) use (&$index, &$fields, &$edges): void {
            foreach ($items as $field) {
                $key = (string)$field->reference;
                $fields[$key] = $field;
                $edges[$key] = $parent ? [$parent] : [];
                if ($field instanceof ParentFieldInterface) {
                    $index($field->getFields(), $key);
                }
            }
        };
        $index($form->getFields());
        $positions = array_flip(array_keys($fields));
        $resolver = new FieldReferenceResolver();
        foreach ($fields as $key => $field) {
            $settings = $field->hasConditions() ? [$field->getConditions()] : [];
            $page = $field->getPage();
            if ($page?->hasConditions()) {
                $settings[] = $page->getConditions();
            }
            foreach ($settings as $config) {
                foreach (ConditionSet::fromArray($config)->rules as $rule) {
                    $expression = References::parseReferenceExpression(str_starts_with($rule->reference, '{') ? $rule->reference : References::field($rule->reference));
                    if ($expression->target !== 'field') {
                        continue;
                    }
                    $source = $resolver->findField($expression->identifier, new ReferenceContext(form: $form));
                    if ($source && $expression->selector !== '' && $source['field'] instanceof ParentFieldInterface) {
                        $source = $resolver->findField($source['path'] . '.' . str_replace(':', '.', $expression->selector), new ReferenceContext(form: $form)) ?? $source;
                    }
                    if ($source) {
                        $sourceKey = (string)$source['field']->reference;
                        // Unversioned Formie 3 rules retain dependency ordering during upgrade.
                        $legacy = $rule->metadata['legacyForward'] ?? !isset($config['version']);
                        if (!$legacy && ($positions[$sourceKey] ?? PHP_INT_MAX) >= $positions[$key]) {
                            throw new RuntimeException('Visibility conditions must use a preceding field.');
                        }
                        $edges[$key][] = $sourceKey;
                        if ($source['field'] instanceof ParentFieldInterface) {
                            // A collection projection includes its children after their own clearing.
                            $children = function(array $items) use (&$children, &$edges, $key): void {
                                foreach ($items as $child) {
                                    $edges[$key][] = (string)$child->reference;
                                    if ($child instanceof ParentFieldInterface) {
                                        $children($child->getFields());
                                    }
                                }
                            };
                            $children($source['field']->getFields());
                        }
                    }
                }
            }
        }
        $visiting = [];
        $visited = [];
        $ordered = [];
        $visit = function(string $key) use (&$visit, &$visiting, &$visited, &$ordered, $edges, $fields): void {
            if (isset($visiting[$key])) {
                throw new RuntimeException('Condition dependency cycle: ' . implode(' → ', [...array_keys($visiting), $key]));
            }
            if (isset($visited[$key])) {
                return;
            }
            $visiting[$key] = true;
            foreach ($edges[$key] ?? [] as $dependency) {
                $visit($dependency);
            }
            unset($visiting[$key]);
            $visited[$key] = true;
            if (isset($fields[$key])) {
                $ordered[] = $fields[$key];
            }
        };
        foreach (array_keys($fields) as $key) {
            $visit($key);
        }
        return $ordered;
    }
}
