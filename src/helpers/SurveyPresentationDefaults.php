<?php
namespace verbb\formie\helpers;

use verbb\formie\fields\Survey;
use verbb\formie\Formie;
use verbb\formie\options\predefined\LikertScale;
use verbb\formie\options\predefined\StarRating;

class SurveyPresentationDefaults
{
    // Public Methods
    // =========================================================================

    public static function likertScaleOptions(): array
    {
        return LikertScale::toFieldOptions();
    }

    public static function ratingScaleOptions(): array
    {
        return StarRating::toFieldOptions();
    }

    public static function resolveOptionsForDisplayType(string $displayType, ?array $fieldDefaults = null): array
    {
        $fieldDefaults ??= Formie::$plugin->getFormDefaults()->resolveFieldTypeDefaults(Survey::class);

        return match ($displayType) {
            Survey::DISPLAY_LIKERT => self::_resolveOptions(
                $fieldDefaults['likertDefaultOptions'] ?? null,
                self::likertScaleOptions(),
            ),
            Survey::DISPLAY_RATING => self::_resolveOptions(
                $fieldDefaults['ratingDefaultOptions'] ?? null,
                self::ratingScaleOptions(),
            ),
            default => [],
        };
    }

    public static function normalizeDefaultOptions(mixed $options): array
    {
        if (!is_array($options)) {
            return [];
        }

        $normalized = [];

        foreach ($options as $option) {
            if (!is_array($option)) {
                continue;
            }

            $label = trim((string)($option['label'] ?? ''));

            if ($label === '') {
                continue;
            }

            $normalized[] = [
                'label' => $label,
                'value' => trim((string)($option['value'] ?? '')),
                'default' => false,
            ];
        }

        return $normalized;
    }

    public static function defaultOptionLabels(array $options): array
    {
        $labels = [];

        foreach (self::normalizeDefaultOptions($options) as $option) {
            $labels[] = $option['label'];
        }

        return $labels;
    }

    public static function defaultOptionsMatch(mixed $value, mixed $classDefault): bool
    {
        return self::defaultOptionLabels(is_array($value) ? $value : [])
            === self::defaultOptionLabels(is_array($classDefault) ? $classDefault : []);
    }


    // Private Methods
    // =========================================================================

    private static function _resolveOptions(mixed $configured, array $fallback): array
    {
        $configured = self::normalizeDefaultOptions($configured);

        return $configured !== [] ? $configured : $fallback;
    }
}
