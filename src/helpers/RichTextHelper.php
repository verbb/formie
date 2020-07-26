<?php
namespace verbb\formie\helpers;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\FileHelper;
use craft\helpers\Json;

use Throwable;

class RichTextHelper
{
    // Public Methods
    // =========================================================================

    public static function getRichTextConfig($key)
    {
        $config = self::_getDefaultConfig();
        $fileConfig = self::_getConfig('formie', 'rich-text.json');

        // Override defaults with any file-based config
        if (is_array($fileConfig)) {
            foreach ($fileConfig as $k => $v) {
                $config[$k] = array_merge($config[$k], $v);
            }
        }

        return ArrayHelper::getValue($config, $key);
    }

    private static function _getDefaultConfig()
    {
        return [
            'forms' => [
                'submitActionMessage' => [
                    'buttons' => ['bold', 'italic'],
                    'rows' => 3,
                ],
                'errorMessage' => [
                    'buttons' => ['bold', 'italic'],
                    'rows' => 3,
                ],
            ],
            'notifications' => [
                'content' => [
                    'buttons' => ['bold', 'italic', 'variableTag'],
                ],
            ],
        ];
    }

    private static function _getConfig(string $dir, string $file = null)
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
