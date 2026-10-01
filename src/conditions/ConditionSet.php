<?php
namespace verbb\formie\conditions;

final readonly class ConditionSet
{
    // Static Methods
    // =========================================================================

    // Stable Formie 3 arrays are accepted only at this boundary. Never discard malformed rows.
    public static function fromArray(array $settings, string $purpose = 'visibility'): self
    {
        $rules = [];
        $input = $settings['conditions'] ?? $settings['rules'] ?? [];

        foreach (is_array($input) ? $input : [null] as $row) {
            $row = is_array($row) ? $row : [];
            $reference = $row['field'] ?? $row['fieldId'] ?? '';

            if (is_array($reference)) {
                $reference = $reference['field'] ?? $reference['fieldId'] ?? '';
            }

            if (is_string($reference)) {
                $reference = [
                    '{submission:formName}' => '{form:name}',
                    '{submission:siteName}' => '{site:name}',
                    '{submission:siteHandle}' => '{site:handle}',
                    '{submission:dateCreated}' => '{submission:date}',
                ][$reference] ?? $reference;
            }
            $operator = $row['condition'] ?? $row['operator'] ?? '';
            $operator = is_string($operator) ? $operator : '';
            $operator = ['==' => '=', 'equals' => '=', 'notEquals' => '!='][$operator] ?? $operator;
            $rules[] = new ConditionRule(is_string($reference) ? $reference : '', $operator, $row['value'] ?? null, $row);
        }
        $mode = $settings['conditionRule'] ?? $settings['mode'] ?? 'all';
        $effect = $settings['showRule'] ?? $settings['effect'] ?? 'show';
        $version = $settings['version'] ?? 1;
        return new self(is_string($mode) ? $mode : '', is_string($effect) ? $effect : '', $rules, $purpose, is_int($version) ? $version : 0);
    }


    // Public Methods
    // =========================================================================

    public function __construct(public string $mode, public string $effect, public array $rules, public string $purpose = 'visibility', public int $version = 1)
    {
    }
}
