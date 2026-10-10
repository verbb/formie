<?php
namespace verbb\formie\helpers;

use verbb\formie\content\ContentEncryption;

use Craft;
use craft\helpers\Json;

final class OperationalSecurity
{
    // Static Methods
    // =========================================================================

    public static function encrypt(string $plain): string
    {
        return base64_encode(Craft::$app->getSecurity()->encryptByKey($plain, Craft::$app->getConfig()->getGeneral()->securityKey));
    }

    public static function decrypt(string $encoded): string|false
    {
        $cipher = base64_decode($encoded, true);

        if ($cipher === false) {
            return false;
        }

        foreach ([Craft::$app->getConfig()->getGeneral()->securityKey, ...ContentEncryption::legacyKeys()] as $key) {
            $plain = Craft::$app->getSecurity()->decryptByKey($cipher, $key);

            if ($plain !== false) {
                return $plain;
            }
        }

        return false;
    }

    public static function fingerprint(string $purpose, mixed $value): string
    {
        return hash('sha256', "formie:$purpose:v1\0" . Json::encode($value));
    }
}
