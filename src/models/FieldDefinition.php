<?php
namespace verbb\formie\models;

use craft\helpers\Json;

/** Shared metadata only; this object has no instance identity or runtime lifecycle. */
final readonly class FieldDefinition
{
    // Properties
    // =========================================================================

    public int $id;
    public string $uid;
    public string $type;
    public string $label;
    public string $handle;
    public array $settings;


    // Public Methods
    // =========================================================================

    public function __construct(array $record)
    {
        $this->id = (int)$record['id'];
        $this->uid = (string)$record['uid'];
        $this->type = (string)$record['type'];
        $this->label = (string)$record['label'];
        $this->handle = (string)$record['handle'];
        $this->settings = Json::decodeIfJson($record['settings'] ?? []) ?: [];
    }
}
