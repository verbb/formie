<?php
namespace verbb\formie\tiptap;

use Tiptap\Core\Extension;
use Tiptap\Utils\InlineStyle;

final class RegisteredTextStyles extends Extension
{
    // Properties
    // =========================================================================

    public static $name = 'formieTextStyles';


    // Public Methods
    // =========================================================================

    public function addOptions(): array
    {
        return ['definitions' => []];
    }

    public function addGlobalAttributes(): array
    {
        $attributes = [];

        foreach ($this->options['definitions'] as $definition) {
            if (!$definition instanceof TextStyleDefinition) {
                continue;
            }

            $attribute = $definition->getAttribute();
            $cssProperty = $definition->getCssProperty();
            $allowedValues = $definition->getAllowedValues();

            $attributes[$attribute] = [
                'default' => null,
                'parseHTML' => static function($DOMNode) use ($cssProperty, $allowedValues): ?string {
                    $value = InlineStyle::getAttribute($DOMNode, $cssProperty);

                    return in_array($value, $allowedValues, true) ? $value : null;
                },
                'renderHTML' => static function($attributes) use ($attribute, $cssProperty, $allowedValues): ?array {
                    $value = $attributes?->{$attribute} ?? null;

                    return in_array($value, $allowedValues, true)
                        ? ['style' => "{$cssProperty}: {$value}"]
                        : null;
                },
            ];
        }

        return [[
            'types' => ['textStyle'],
            'attributes' => $attributes,
        ]];
    }
}
