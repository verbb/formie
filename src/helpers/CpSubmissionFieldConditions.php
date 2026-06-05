<?php
namespace verbb\formie\helpers;

use Craft;

class CpSubmissionFieldConditions
{
    // Constants
    // =========================================================================

    public const FOLLOW = 'follow';
    public const MUTED = 'muted';
    public const SHOW_ALL = 'show-all';

    /** Client-side conditions module display mode when fields should be hidden in CP. */
    public const CLIENT_DISPLAY_HIDE = 'hide';


    // Static Methods
    // =========================================================================

    public static function values(): array
    {
        return [
            self::FOLLOW,
            self::MUTED,
            self::SHOW_ALL,
        ];
    }

    public static function normalize(?string $value, string $fallback = self::FOLLOW): string
    {
        $value = is_string($value) ? trim($value) : '';

        if ($value === '') {
            $value = $fallback;
        }

        return in_array($value, self::values(), true) ? $value : self::FOLLOW;
    }

    public static function pluginOptions(): array
    {
        return [
            [
                'value' => self::FOLLOW,
                'label' => Craft::t('formie', 'Follow field conditions'),
            ],
            [
                'value' => self::MUTED,
                'label' => Craft::t('formie', 'Follow field conditions (show hidden fields collapsed)'),
            ],
            [
                'value' => self::SHOW_ALL,
                'label' => Craft::t('formie', 'Show all fields'),
            ],
        ];
    }

    public static function formOptions(): array
    {
        return array_merge([
            [
                'value' => '',
                'label' => Craft::t('formie', 'Use Formie default'),
            ],
        ], self::pluginOptions());
    }

    public static function clientDisplayMode(string $mode): string
    {
        return $mode === self::MUTED ? self::MUTED : self::CLIENT_DISPLAY_HIDE;
    }
}
