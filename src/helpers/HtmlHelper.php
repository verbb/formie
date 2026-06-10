<?php
namespace verbb\formie\helpers;

use Craft;
use craft\helpers\Json;

class HtmlHelper
{
    // Static Methods
    // =========================================================================

    public static function getHtmlEditorConfig(string $key): array
    {
        $config = self::_getDefaultConfig();
        $fileConfig = self::_getConfig('formie', 'html.json');

        if (is_array($fileConfig)) {
            foreach ($fileConfig as $configKey => $value) {
                if (!is_array($value)) {
                    continue;
                }

                $config[$configKey] = array_merge($config[$configKey] ?? [], $value);
            }
        }

        return ArrayHelper::getValue($config, $key, []);
    }


    // Private Methods
    // =========================================================================

    private static function _getDefaultConfig(): array
    {
        return [
            'fields' => [
                'html' => [
                    'rows' => 12,
                    'tabSize' => 4,
                    'lineNumbers' => true,
                    'language' => 'html',
                ],
            ],
        ];
    }

    private static function _getConfig(string $dir, string $file = null): bool|array
    {
        if (!$file) {
            return false;
        }

        $path = Craft::$app->getPath()->getConfigPath() . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $file;

        if (!is_file($path)) {
            return false;
        }

        return Json::decode(file_get_contents($path));
    }
}
