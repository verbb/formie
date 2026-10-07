<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;

use craft\helpers\Json;

class RecipientTokenHelper
{
    // Static Methods
    // =========================================================================

    public static function encode(mixed $value, string $type): string
    {
        return 'recipient:v1:' . hash_hmac('sha256', $type . "\0" . Json::encode($value), Formie::$plugin->getSettings()->getSecurityKey());
    }

    public static function encodeOption(array $option, int|string|null $index = null): string
    {
        return self::encode(self::optionPayload($option, $index), self::TYPE_OPTION);
    }

    public static function encodeHidden(mixed $value): string
    {
        return self::encode($value, self::TYPE_HIDDEN);
    }

    public static function decode(string $token): mixed
    {
        return $token;
    }

    public static function decodePayload(string $token): ?array
    {
        return null;
    }

    public static function optionPayload(array $option, int|string|null $index = null): array
    {
        $label = (string)($option['label'] ?? '');
        $value = (string)($option['value'] ?? '');

        return [
            'type' => self::TYPE_OPTION,
            'id' => self::optionId($option, $index),
            'label' => $label,
            'value' => $value,
        ];
    }

    public static function optionId(array $option, int|string|null $index = null): string
    {
        $id = $option['uid'] ?? $option['id'] ?? null;

        if ($id !== null && $id !== '') {
            return (string)$id;
        }

        $label = (string)($option['label'] ?? '');
        $value = (string)($option['value'] ?? '');

        // Label + value gives recipient rows a stable identity across reorder,
        // while still allowing several labels to route to the same email target.
        return hash('sha256', $label . "\0" . $value);
    }


    // Constants
    // =========================================================================

    public const TYPE_OPTION = 'recipient-option';
    public const TYPE_HIDDEN = 'recipient-hidden';
}
