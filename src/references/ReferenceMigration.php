<?php
namespace verbb\formie\references;

/** Lossless read/save migration restricted to known reference-bearing settings. */
final class ReferenceMigration
{
    // Static Methods
    // =========================================================================

    public static function integrationSlots(array $settings): array
    {
        foreach ($settings as $key => $value) {
            if (!is_array($value)) {
                continue;
            }
            if (str_ends_with(strtolower((string)$key), 'fieldmapping') || in_array($key, ['attributeMapping', 'emailSendMapping'], true)) {
                foreach ($value as $destination => $slot) {
                    $settings[$key][$destination] = ReferenceSlot::fromStored($slot)->toArray();
                }
            } else {
                $settings[$key] = self::integrationSlots($value);
            }
        }
        return $settings;
    }
}
