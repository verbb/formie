<?php
namespace verbb\formie\conditions;

final class ConditionMigration
{
    // Static Methods
    // =========================================================================

    public static function migrate(array $data): array
    {
        if (isset($data['conditions']) && is_array($data['conditions']) && isset($data['conditionRule'])) {
            $legacy = !isset($data['version']);
            $data['version'] ??= 1;

            foreach ($data['conditions'] as &$row) {
                if (is_array($row)) {
                    $operator = $row['condition'] ?? '';

                    if (is_string($operator)) {
                        $row['condition'] = ['==' => '=', 'equals' => '=', 'notEquals' => '!='][$operator] ?? $operator;
                    }
                    $row['legacyForward'] ??= $legacy;
                }
            }
            unset($row);
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::migrate($value);
            }
        }
        return $data;
    }
}
