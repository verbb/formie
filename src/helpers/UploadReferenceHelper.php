<?php
namespace verbb\formie\helpers;

use verbb\formie\content\FieldStorageCodec;
use verbb\formie\elements\Form;
use verbb\formie\fields\FileUpload;
use verbb\formie\fields\Group;
use verbb\formie\fields\MissingField;
use verbb\formie\fields\Repeater;

use craft\helpers\Json;

use Throwable;

class UploadReferenceHelper
{
    // Static Methods
    // =========================================================================

    public static function schema(Form $form): array
    {
        $schema = [];
        $walk = function(array $fields) use (&$walk, &$schema): bool {
            $uploads = false;

            foreach ($fields as $field) {
                $kind = $field instanceof FileUpload ? 'upload' : ($field instanceof Repeater ? 'repeater' : ($field instanceof Group ? 'group' : ($field instanceof MissingField ? 'unknown' : 'leaf')));
                $contains = $kind === 'upload' || $kind === 'unknown';

                if ($kind === 'group' || $kind === 'repeater') {
                    $contains = $walk($field->getFields());
                }

                $schema[$field->uid] = ['kind' => $kind, 'handle' => $field->handle, 'uploads' => $contains];
                $uploads = $uploads || $contains;
            }

            return $uploads;
        };
        $walk($form->getFields());
        ksort($schema);

        return $schema;
    }

    public static function schemaFromLayouts(int $layoutId, array $layouts): array
    {
        $schema = [];
        $visited = [];
        $walk = function(int $id) use (&$walk, &$schema, &$visited, $layouts): bool {
            if (isset($visited[$id])) {
                return $visited[$id];
            }

            $visited[$id] = false;
            $uploads = false;

            foreach ($layouts[$id] ?? [] as $field) {
                $kind = is_a($field['type'], FileUpload::class, true) ? 'upload' : (is_a($field['type'], Repeater::class, true) ? 'repeater' : (is_a($field['type'], Group::class, true) ? 'group' : (!class_exists($field['type']) || is_a($field['type'], MissingField::class, true) ? 'unknown' : 'leaf')));
                $contains = $kind === 'upload' || $kind === 'unknown';

                if ($kind === 'group' || $kind === 'repeater') {
                    $settings = Json::decodeIfJson($field['settings']) ?: [];
                    $contains = $walk((int)($settings['nestedLayoutId'] ?? 0));
                }

                $schema[$field['uid']] = ['kind' => $kind, 'handle' => $field['handle'], 'uploads' => $contains];
                $uploads = $uploads || $contains;
            }

            return $visited[$id] = $uploads;
        };
        $walk($layoutId);
        ksort($schema);

        return $schema;
    }

    public static function fingerprint(array $schema): string
    {
        // Labels and other presentation settings cannot change stored file usage.
        return hash('sha256', Json::encode(array_filter($schema, fn($field) => $field['uploads'])));
    }

    public static function extract(mixed $content, array $schema, ?string $exceptContentKey = null): array
    {
        $ids = [];
        $unknown = false;

        if (!array_filter($schema, fn($field) => $field['uploads'])) {
            return ['ids' => [], 'unknown' => false];
        }

        $walk = function(mixed $values, string $path = '') use (&$walk, &$ids, &$unknown, $schema, $exceptContentKey): void {
            if (!is_array($values)) {
                $unknown = true;

                return;
            }

            foreach ($values as $uid => $stored) {
                $field = $schema[$uid] ?? null;

                if (!$field) {
                    // Relocated fields can remain under an old container UID. Only known
                    // field UIDs count; arbitrary numbers in unknown content never do.
                    try {
                        $value = is_array($stored) || FieldStorageCodec::isEncryptedValue($stored) ? self::_decode($stored) : null;

                        if (is_array($value)) {
                            $walk($value, $path);
                        }
                    } catch (Throwable) {
                        // An unreadable former parent may still contain a known,
                        // relocated upload UID. Absence cannot be established.
                        $unknown = true;
                    }

                    continue;
                }

                if ($field['kind'] === 'leaf') {
                    continue;
                }

                if ($field['kind'] === 'unknown') {
                    $unknown = true;

                    continue;
                }

                $key = ltrim($path . '.' . $field['handle'], '.');

                if ($field['kind'] === 'upload' && $key === $exceptContentKey) {
                    continue;
                }

                try {
                    $value = self::_decode($stored);
                } catch (Throwable) {
                    $unknown = $unknown || $field['uploads'];

                    continue;
                }

                if ($value === null || $value === '' || $value === []) {
                    continue;
                }

                if ($field['kind'] === 'upload') {
                    foreach (is_array($value) ? $value : [$value] as $id) {
                        if ((is_int($id) || (is_string($id) && ctype_digit($id))) && (int)$id > 0 && (int)$id <= 2147483647) {
                            $ids[(int)$id] = (int)$id;
                        } else {
                            $unknown = true;
                        }
                    }
                } elseif ($field['kind'] === 'repeater' && is_array($value)) {
                    foreach ($value as $row => $children) {
                        $walk($children, $key . '.' . $row);
                    }
                } else {
                    $walk($value, $key);
                }
            }
        };

        try {
            $walk(is_string($content) ? Json::decode($content) : ($content ?? []));
        } catch (Throwable) {
            $unknown = true;
        }

        sort($ids, SORT_NUMERIC);

        return ['ids' => array_values($ids), 'unknown' => $unknown];
    }

    private static function _decode(mixed $stored): mixed
    {
        if (is_array($stored) && (array_key_exists('__formie_enc', $stored) || array_key_exists('__formie_encrypted', $stored)) && !FieldStorageCodec::isEncryptedValue($stored)) {
            throw new \RuntimeException('Malformed stored upload content.');
        }

        $value = FieldStorageCodec::decode($stored);

        if (is_string($value) && (str_starts_with($value, '[') || str_starts_with($value, '{'))) {
            $value = Json::decode($value);
        }

        return $value;
    }

}
