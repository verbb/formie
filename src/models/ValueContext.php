<?php
namespace verbb\formie\models;

class ValueContext
{
    public const TYPE_STRING = 'string';
    public const TYPE_ARRAY = 'array';
    public const TYPE_EXPORT = 'export';
    public const TYPE_REFERENCE = 'reference';
    public const TYPE_REFERENCE_BLOCK = 'referenceBlock';
    public const TYPE_SUMMARY = 'summary';
    public const TYPE_CONDITION = 'condition';
    public const TYPE_INTEGRATION = 'integration';

    // Deprecated
    public const TYPE_JSON = 'json';
    public const TYPE_EMAIL = 'email';
    public const TYPE_VARIABLE = 'variable';

    public function __construct(
        public string $type,
        public array $params = [],
    ) {
    }

    public static function string(): self
    {
        return new self(self::TYPE_STRING);
    }

    public static function array(): self
    {
        return new self(self::TYPE_ARRAY);
    }

    public static function json(): self
    {
        // Deprecated in 4.0.0 Use `array()` instead.
        return new self(self::TYPE_JSON);
    }

    public static function export(): self
    {
        return new self(self::TYPE_EXPORT);
    }

    public static function reference(mixed $notification = null): self
    {
        return new self(self::TYPE_REFERENCE, [
            'notification' => $notification,
        ]);
    }

    public static function referenceBlock(mixed $notification): self
    {
        return new self(self::TYPE_REFERENCE_BLOCK, [
            'notification' => $notification,
        ]);
    }

    public static function summary(): self
    {
        return new self(self::TYPE_SUMMARY);
    }

    public static function condition(): self
    {
        return new self(self::TYPE_CONDITION);
    }

    public static function email(mixed $notification): self
    {
        // Deprecated in 4.0.0 Use `referenceBlock()` instead.
        return self::referenceBlock($notification);
    }

    public static function integration(mixed $integrationField, mixed $integration, string $fieldKey = ''): self
    {
        return new self(self::TYPE_INTEGRATION, [
            'integrationField' => $integrationField,
            'integration' => $integration,
            'fieldKey' => $fieldKey,
        ]);
    }

    public static function variable(mixed $notification): self
    {
        // Deprecated in 4.0.0 Use `reference()` instead.
        return self::reference($notification);
    }
}
