<?php
namespace verbb\formie\helpers;

use craft\enums\Color;

class StatusColorHelper
{
    /**
     * Maps Formie status colors to Craft CP `Color` enums.
     *
     * Default form lifecycle handles are aligned with Craft entry status styling:
     * active → live (teal), draft → pending (orange), archived → disabled (gray).
     */
    public static function resolveColor(?string $color, ?string $handle = null): Color
    {
        if ($handle) {
            $lifecycleColor = match ($handle) {
                'active', 'new' => Color::Teal,
                'draft' => Color::Orange,
                'archived' => Color::Gray,
                default => null,
            };

            if ($lifecycleColor) {
                return $lifecycleColor;
            }

            $fromHandle = Color::tryFromStatus($handle);

            if ($fromHandle) {
                return $fromHandle;
            }
        }

        if ($color) {
            $resolved = Color::tryFrom($color) ?? Color::tryFromStatus($color);

            if ($resolved) {
                return $resolved;
            }

            $mapped = match ($color) {
                'light', 'grey' => Color::Gray,
                'turquoise' => Color::Teal,
                default => null,
            };

            if ($mapped) {
                return $mapped;
            }
        }

        return Color::Gray;
    }
}
