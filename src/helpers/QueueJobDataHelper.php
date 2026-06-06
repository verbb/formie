<?php
namespace verbb\formie\helpers;

use craft\helpers\Json;

use Throwable;

class QueueJobDataHelper
{
    // Static Methods
    // =========================================================================

    public static function sanitizeForSerialization(mixed $value): mixed
    {
        if (is_string($value)) {
            return StringHelper::convertToUtf8($value);
        }

        if (is_array($value)) {
            $sanitized = [];

            foreach ($value as $key => $item) {
                $sanitizedKey = is_string($key) ? StringHelper::convertToUtf8($key) : $key;
                $sanitized[$sanitizedKey] = self::sanitizeForSerialization($item);
            }

            return $sanitized;
        }

        if (is_object($value)) {
            try {
                return self::sanitizeForSerialization(Json::decode(Json::encode($value)));
            } catch (Throwable) {
                return '[unserializable]';
            }
        }

        return $value;
    }

    public static function sanitizeJobObject(object $job): object
    {
        foreach (get_object_vars($job) as $property => $value) {
            $job->$property = self::sanitizeForSerialization($value);
        }

        return $job;
    }
}
