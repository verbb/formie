<?php
namespace verbb\formie\compatibility\fields;

use verbb\formie\models\BrowserModuleEntry;

use Craft;

/** Bounded adapter for the verified stable Formie 3 field declaration API. */
class LegacyBrowserModules
{
    // Static Methods
    // =========================================================================

    public static function fromField(object $field): array
    {
        if (!method_exists($field, 'getFrontEndJsModules')) {
            return [];
        }
        $legacy = $field->getFrontEndJsModules();
        if (!$legacy) {
            return [];
        }
        Craft::$app->getDeprecator()->log(get_class($field) . '::getFrontEndJsModules', 'getFrontEndJsModules() is deprecated. Declare browserModules() with registered module IDs. Executable src URLs are not accepted.');
        $entries = [];
        foreach (array_is_list($legacy) ? $legacy : [$legacy] as $module) {
            $name = (string)($module['module'] ?? '');
            if ($name === '') {
                continue;
            }
            $id = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', preg_replace('/^Formie/', '', $name)));
            $config = (array)($module['settings'] ?? []);
            if ($name === 'FormieSignature') {
                $config = ['options' => $config];
            }
            // Source URLs never cross the wire. Third-party names require an
            // explicit trusted browser registry entry under the legacy namespace.
            $entries[] = new BrowserModuleEntry([
                'moduleId' => (str_starts_with($name, 'Formie') ? 'formie:' : 'legacy:') . $id,
                'config' => $config,
            ]);
        }
        return $entries;
    }
}
