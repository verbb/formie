<?php
namespace verbb\formie\content;

use verbb\formie\base\IntegrationInterface;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Notification;

/** @internal Validated projection input for Formie's content pipeline. */
final readonly class FieldValueProjectionContext
{
    // Static Methods
    // =========================================================================

    public static function string(): self
    {
        return new self(FieldValueProjection::String);
    }

    public static function data(): self
    {
        return new self(FieldValueProjection::Data);
    }

    public static function export(): self
    {
        return new self(FieldValueProjection::Export);
    }

    public static function reference(): self
    {
        return new self(FieldValueProjection::Reference);
    }

    public static function referenceBlock(Notification $notification): self
    {
        return new self(FieldValueProjection::ReferenceBlock, notification: $notification);
    }

    public static function summary(): self
    {
        return new self(FieldValueProjection::Summary);
    }

    public static function condition(): self
    {
        return new self(FieldValueProjection::Condition);
    }

    public static function integration(IntegrationField $integrationField, IntegrationInterface $integration, string $fieldKey = ''): self
    {
        return new self(
            FieldValueProjection::Integration,
            integrationField: $integrationField,
            integration: $integration,
            fieldKey: $fieldKey,
        );
    }


    // Private Methods
    // =========================================================================

    private function __construct(
        public FieldValueProjection $projection,
        public ?Notification $notification = null,
        public ?IntegrationField $integrationField = null,
        public ?IntegrationInterface $integration = null,
        public string $fieldKey = '',
    ) {
    }
}
