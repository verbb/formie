<?php
namespace verbb\formie\options;

use verbb\formie\fields\Checkboxes;
use verbb\formie\fields\Dropdown;
use verbb\formie\fields\Radio;
use verbb\formie\fields\Recipients;
use verbb\formie\helpers\OptionsMode;
use verbb\formie\models\OptionSource;

final class OptionSourceConfigHelper
{
    // Static Methods
    // =========================================================================

    public static function allowedTypesForFieldClass(string $fieldClass): array
    {
        if ($fieldClass === Recipients::class) {
            return ['integration'];
        }

        if (in_array($fieldClass, [Dropdown::class, Radio::class, Checkboxes::class], true)) {
            return ['predefined', 'integration'];
        }

        return ['predefined'];
    }

    public static function normalizeOptionSource(mixed $optionSource, string $optionsMode, ?array $allowedTypes = null): ?array
    {
        if (in_array($optionsMode, [OptionsMode::STATIC, OptionsMode::TEMPLATE], true)) {
            return null;
        }

        $model = OptionSource::fromConfig($optionSource);

        if (!$model || !$model->type) {
            return null;
        }

        $allowedTypes ??= ['predefined', 'integration'];

        if (!in_array($model->type, $allowedTypes, true)) {
            return null;
        }

        return $model->toConfig() ?: null;
    }
}
