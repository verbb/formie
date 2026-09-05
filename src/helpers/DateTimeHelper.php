<?php
namespace verbb\formie\helpers;

use craft\helpers\DateTimeHelper as CraftDateTimeHelper;

/**
 * Date-field helpers for subfield layout order derived from date/time format settings.
 *
 * Kept separate from Craft's DateTimeHelper so migrations and Date field scaffolding can
 * share one ordering map without pulling Craft 4→5 DateTime model shims into F4.
 */
class DateTimeHelper extends CraftDateTimeHelper
{
    // Constants
    // =========================================================================

    public const FORMAT_HANDLE_MAP = [
        'Y' => 'year',
        'y' => 'year',
        'm' => 'month',
        'n' => 'month',
        'M' => 'month',
        'F' => 'month',
        'd' => 'day',
        'j' => 'day',
        'H' => 'hour',
        'h' => 'hour',
        'G' => 'hour',
        'g' => 'hour',
        'i' => 'minute',
        's' => 'second',
        'A' => 'ampm',
        'a' => 'ampm',
    ];


    // Static Methods
    // =========================================================================

    /**
     * Returns the subfield handles for a Date field, in the order defined by its date/time format.
     */
    public static function getSubfieldOrder(array $settings): array
    {
        $format = (($settings['includeDate'] ?? true) ? ($settings['dateFormat'] ?? 'Y-m-d') : '') .
            (($settings['includeTime'] ?? true) ? ($settings['timeFormat'] ?? 'H:i') : '');

        // Strip out any separators, leaving just the formatting characters
        $format = preg_replace('/[.\-:\/ ]/', '', $format) ?? '';

        $handles = [];

        foreach (str_split($format) as $char) {
            $handle = self::FORMAT_HANDLE_MAP[$char] ?? null;

            if ($handle && !in_array($handle, $handles, true)) {
                $handles[] = $handle;
            }
        }

        return $handles;
    }

    /**
     * Sorts an array of subfield configs (each with a `handle`) into date/time format order.
     * Any subfields not represented in the format retain their relative order at the end.
     */
    public static function orderSubfieldConfigs(array $settings, array $fields): array
    {
        $order = self::getSubfieldOrder($settings);

        foreach ($fields as $field) {
            $handle = $field['handle'] ?? null;

            if ($handle && !in_array($handle, $order, true)) {
                $order[] = $handle;
            }
        }

        usort($fields, function(array $a, array $b) use ($order): int {
            return array_search(($a['handle'] ?? null), $order, true) <=> array_search(($b['handle'] ?? null), $order, true);
        });

        return $fields;
    }
}
