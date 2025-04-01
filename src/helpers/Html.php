<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\helpers\ArrayHelper;

use Craft;
use craft\helpers\Html as CraftHtmlHelper;

class Html extends CraftHtmlHelper
{
    // Static Methods
    // =========================================================================

    public static function getInputNameAttribute(array $names): string
    {
        // Normalize the names first, in case items already are in name-like syntax
        $names = self::normalizeNames($names);

        $first = array_shift($names);

        if ($names) {
            return $first . '[' . implode('][', $names) . ']';
        }

        return $first ?? '';
    }

    public static function getInputIdAttribute(array $names): string
    {
        // Normalize the names first, in case items already are in name-like syntax
        $names = self::normalizeNames($names);

        return implode('-', array_filter($names));
    }

    public static function mergeAttributes(array $attrs, array $extraAttrs, array $options = []): array
    {
        // Ensure to normalise both arrays
        $attrs = static::normalizeTagAttributes($attrs);
        $extraAttrs = static::normalizeTagAttributes($extraAttrs);

        // Only `class`, `data`, 'style', 'aria' should recursively merge
        $class = ArrayHelper::remove($attrs, 'class', []);
        $data = ArrayHelper::remove($attrs, 'data', []);
        $style = ArrayHelper::remove($attrs, 'style', []);
        $aria = ArrayHelper::remove($attrs, 'aria', []);

        $extraClass = ArrayHelper::remove($extraAttrs, 'class', []);
        $extraData = ArrayHelper::remove($extraAttrs, 'data', []);
        $extraStyle = ArrayHelper::remove($extraAttrs, 'style', []);
        $extraAria = ArrayHelper::remove($extraAttrs, 'aria', []);

        // Merge the two arrays now complex values are removed
        $merged = array_merge($attrs, $extraAttrs);

        // Check if we're resetting classes in either array
        if (isset($options['resetClassA'])) {
            $extraClass = [];
        }

        if (isset($options['resetClassB'])) {
            $class = [];
        }

        // Add back and merge the complex attributes
        $merged['class'] = array_unique(array_merge($class, $extraClass));
        $merged['data'] = array_merge($data, $extraData);
        $merged['style'] = array_merge($style, $extraStyle);
        $merged['aria'] = array_merge($aria, $extraAria);

        // Filter just `null` and `false` values
        return ArrayHelper::filterNullFalse($merged); 
    }

    public static function getFieldClassKey(object $class): string
    {
        $className = StringHelper::toCamelCase(StringHelper::toKebabCase($class::className()));

        // TODO: remove this extra handling for the next version, but will be a breaking change
        if ($className === 'radio') {
            return 'radioButtons';
        }

        if ($className === 'date') {
            return 'dateTime';
        }

        if ($className === 'email') {
            return 'emailAddress';
        }

        if ($className === 'hidden') {
            return 'hiddenField';
        }

        if ($className === 'phone') {
            return 'phoneNumber';
        }

        return $className;
    }

    public static function getFieldClassHandles(): array
    {
        $handles = [];

        $fields = Formie::$plugin->getFields()->getRegisteredFields(false);

        foreach ($fields as $field) {
            $handles[] = self::getFieldClassKey($field);
        }

        return $handles;
    }

    public static function mergeHtmlConfigs(array $config, array $extraConfig): array
    {
        $mergedConfigs = [];

        foreach ($config as $key => $keyConfig) {
            $extraKeyConfig = null;

            // Don't use a null coalescene operator here, as `null` is a valid value to provide
            if (array_key_exists($key, $extraConfig)) {
                $extraKeyConfig =  $extraConfig[$key];
            }

            // If just a plain setting, that's easy
            if (!is_array($keyConfig) || !is_array($extraKeyConfig)) {
                $mergedConfigs[$key] = $keyConfig ?? $extraKeyConfig;
            } else if (in_array($key, self::getFieldClassHandles())) {
                // Special case for field-class-specific fields, they're nested.
                $mergedConfigs[$key] = self::mergeHtmlConfigs($keyConfig, $extraKeyConfig);
            } else {
                // Merge the first-level settings
                $mergedConfig = array_merge($keyConfig, $extraKeyConfig);

                // Merge the attributes
                $attrs = $keyConfig['attributes'] ?? [];
                $extraAttrs = $extraKeyConfig['attributes'] ?? [];

                // Pass in extra options for resetting classes before they're merged
                $options = array_filter([
                    'resetClassA' => $keyConfig['resetClass'] ?? false,
                    'resetClassB' => $extraKeyConfig['resetClass'] ?? false,
                ]);

                $mergedAttributes = self::mergeAttributes($attrs, $extraAttrs, $options);

                if ($mergedAttributes) {
                    $mergedConfig['attributes'] = $mergedAttributes;
                }

                if ($mergedConfig) {
                    $mergedConfigs[$key] = $mergedConfig;
                }
            }
        }

        // Check if there are any items in `$extraConfig` that aren't in `$config` and add them
        // No need to merge because there's not in `$config`! Be sure to use a placeholder value
        // that couldn't possibly be valid, to cater for `[]`, `null` and `false` which are valid values.
        foreach ($extraConfig as $key => $extraKeyConfig) {
            $keyConfig = $config[$key] ?? '__notfound__';

            if ($keyConfig === '__notfound__') {
                $mergedConfigs[$key] = $extraKeyConfig;
            }
        }

        return $mergedConfigs;
    }

    public static function getTagAttributes(array $attributes): array
    {
        $tagAttributes = [];
        $attributeString = trim(static::renderTagAttributes($attributes));

        $pattern = '/\b([^\s=]+)(?:=("[^"]*"|\'[^\']*\'|[^"\s\']*))?/';
        preg_match_all($pattern, $attributeString, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $tagAttributes[$match[1]] = isset($match[2]) ? trim($match[2], "\"'") : true;
        }

        return $tagAttributes;
    }


    // Private Methods
    // =========================================================================

    private static function normalizeNames($names)
    {
        $normalizedNames = [];

        // Normalise any strings already containing a name-formatted string
        foreach ($names as $key => $name) {
            $name = str_replace([']'], [''], $name);

            // Check for when passing in just `[]`
            if ($name === '[') {
                $normalizedNames[] = '';
            } else {
                $normalizedNames = array_merge($normalizedNames, explode('[', $name));
            }
        }

        return $normalizedNames;
    }

}