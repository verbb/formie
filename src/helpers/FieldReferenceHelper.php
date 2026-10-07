<?php
namespace verbb\formie\helpers;

use verbb\formie\base\FieldInterface;
use verbb\formie\base\ParentFieldInterface;

class FieldReferenceHelper
{
    // Static Methods
    // =========================================================================

    public static function resolveClientFieldKey(string $reference, array $fieldMap = []): string
    {
        $trimmedReference = trim($reference);

        if ($trimmedReference === '') {
            return $reference;
        }

        if (isset($fieldMap[$trimmedReference])) {
            return (string)$fieldMap[$trimmedReference];
        }

        return $trimmedReference;
    }

    public static function getClientFieldReferenceMap(array $fields): array
    {
        return self::_buildClientFieldReferenceMap($fields);
    }

    private static function _buildClientFieldReferenceMap(array $fields, string $uidPrefix = '', string $referencePrefix = ''): array
    {
        $map = [];

        foreach ($fields as $field) {
            if (!$field instanceof FieldInterface) {
                continue;
            }

            $uid = (string)($field->uid ?? '');
            $reference = (string)($field->reference ?? '');
            $fieldKey = (string)$field->valueKey();
            $fullUid = $uidPrefix && $uid ? "{$uidPrefix}.{$uid}" : $uid;
            $fullReference = $referencePrefix && $reference ? "{$referencePrefix}.{$reference}" : $reference;

            if ($uid && $fieldKey) {
                $map[$uid] = $fieldKey;
            }

            if ($fullUid && $fieldKey) {
                $map[$fullUid] = $fieldKey;
            }

            if ($reference && $fieldKey) {
                $map[$reference] = $fieldKey;
            }

            if ($fullReference && $fieldKey) {
                $map[$fullReference] = $fieldKey;
            }

            if ($field instanceof ParentFieldInterface) {
                $map += self::_buildClientFieldReferenceMap(
                    $field->getFields(),
                    $fullUid ?: $uidPrefix,
                    $fullReference ?: $referencePrefix
                );
            }
        }

        return $map;
    }
}
