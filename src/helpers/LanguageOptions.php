<?php
namespace verbb\formie\helpers;

use Craft;

class LanguageOptions
{
    // Static Methods
    // =========================================================================

    public static function buildOptions(array $languages): array
    {
        $locale = Craft::$app->getLocale()->getLanguageID();
        $options = [];

        foreach ($languages as $label => $value) {
            if ($value === 'auto') {
                $options[] = [
                    'label' => Craft::t('formie', 'Auto'),
                    'value' => $value,
                ];
                continue;
            }

            $display = LocaleDataHelper::localeDisplayName((string)$value, $locale);

            if ($display === (string)$value) {
                $display = LocaleDataHelper::languageName((string)$value, $locale);
            }

            if ($display === '' || $display === (string)$value) {
                $display = $label;
            }

            $options[] = [
                'label' => $display,
                'value' => $value,
            ];
        }

        return $options;
    }
}
