<?php
namespace verbb\formie\helpers;

use verbb\formie\base\ParentFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\fields\FileUpload;

class FileUploadRetentionHelper
{
    // Static Methods
    // =========================================================================

    public static function collectFieldsWithAssetRetention(Form $form): array
    {
        $collected = [];

        foreach (self::_walkFileUploadFields($form->getFields()) as $field) {
            $collected[$field->uid] = $field;
        }

        return array_values($collected);
    }

    public static function fieldHasAssetRetention(FileUpload $field): bool
    {
        return DataRetentionHelper::isActive($field->assetDataRetention, $field->assetDataRetentionValue);
    }

    public static function resolveFileUploadFieldForContentKey(Form $form, string $contentKey): ?FileUpload
    {
        $segments = explode('.', $contentKey);
        $scope = $form->getFields();
        $current = null;
        $parent = null;
        $row = null;

        foreach ($segments as $segment) {
            if ($segment === '') {
                continue;
            }

            if (ctype_digit($segment)) {
                $row = $segment;
                continue;
            }

            $current = null;

            foreach ($scope as $field) {
                if ($field->handle === $segment) {
                    $current = $field;
                    break;
                }
            }

            if (!$current) {
                return null;
            }

            if ($parent) {
                $current = $current->withParentField($parent, $row);
            }

            if ($current instanceof ParentFieldInterface) {
                $scope = $current->getFields();
                $parent = $current;
                $row = null;
            }
        }

        return $current instanceof FileUpload ? $current : null;
    }

    private static function _walkFileUploadFields(array $fields): array
    {
        $collected = [];

        foreach ($fields as $field) {
            if ($field instanceof FileUpload && self::fieldHasAssetRetention($field)) {
                $collected[] = $field;
            }

            if ($field instanceof ParentFieldInterface) {
                array_push($collected, ...self::_walkFileUploadFields($field->getFields()));
            }
        }

        return $collected;
    }
}
