<?php
namespace verbb\formie\helpers;

use craft\enums\Color;

class StatusColorHelper
{
    // Static Methods
    // =========================================================================

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

            $fromHandle = self::_fromStatus($handle);

            if ($fromHandle) {
                return $fromHandle;
            }
        }

        if ($color) {
            $resolved = Color::tryFrom($color) ?? self::_fromStatus($color);

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

    private static function _fromStatus(string $status): ?Color
    {
        if (method_exists(Color::class, 'tryFromStatus')) {
            return Color::tryFromStatus($status);
        }

        // Craft's status aliases predate its Color enum helper, added in 5.2.
        return match ($status) {
            'on', 'live', 'active', 'enabled', 'turquoise' => Color::Teal,
            'off', 'suspended', 'expired' => Color::Red,
            'warning' => Color::Amber,
            'pending' => Color::Orange,
            'grey' => Color::Gray,
            default => Color::tryFrom($status),
        };
    }
}
