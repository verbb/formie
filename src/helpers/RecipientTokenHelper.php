<?php
namespace verbb\formie\helpers;

use craft\helpers\Json;

class RecipientTokenHelper
{
    // Constants
    // =========================================================================

    public const TYPE_OPTION = 'recipient-option';
    public const TYPE_HIDDEN = 'recipient-hidden';


    // Static Methods
    // =========================================================================

    public static function encode(mixed $value, string $type): string
    {
        return StringHelper::encenc(Json::encode([
            'type' => $type,
            'value' => $value,
        ]));
    }

    public static function encodeOption(array $option, int|string|null $index = null): string
    {
        return StringHelper::encenc(Json::encode(self::optionPayload($option, $index)));
    }

    public static function encodeHidden(mixed $value): string
    {
        return self::encode($value, self::TYPE_HIDDEN);
    }

    public static function decode(string $token): mixed
    {
        $payload = self::decodePayload($token);

        if (is_array($payload)) {
            return $payload['value'] ?? '';
        }

        return StringHelper::decdec($token);
    }

    public static function decodePayload(string $token): ?array
    {
        $value = StringHelper::decdec($token);

        if (!is_string($value) || !Json::isJsonObject($value)) {
            return null;
        }

        $payload = Json::decodeIfJson($value);

        if (!is_array($payload)) {
            return null;
        }

        $type = $payload['type'] ?? null;

        if (!in_array($type, [self::TYPE_OPTION, self::TYPE_HIDDEN], true)) {
            return null;
        }

        return $payload;
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
}
