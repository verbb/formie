<?php
namespace verbb\formie\models;

use InvalidArgumentException;

final readonly class BrowserModuleManifest
{
    // Constants
    // =========================================================================

    public const CONTRACT_VERSION = 2;


    // Properties
    // =========================================================================

    public readonly string $surface;
    public readonly array $entries;


    // Public Methods
    // =========================================================================

    public function __construct(string $surface, array $entries = [], int $contractVersion = self::CONTRACT_VERSION)
    {
        BrowserModule::validateSurface($surface);

        if ($contractVersion !== self::CONTRACT_VERSION) {
            throw new InvalidArgumentException('Unsupported browser module contractVersion. Update Formie and its browser packages together.');
        }

        foreach ($entries as $entry) {
            if (!$entry instanceof BrowserModuleEntry) {
                throw new InvalidArgumentException('Browser module manifests require completed BrowserModuleEntry values.');
            }
        }

        $keys = array_map(static fn(BrowserModuleEntry $entry): string => $entry->key, $entries);
        if (count(array_unique($keys)) !== count($entries) || in_array('', $keys, true)) {
            throw new InvalidArgumentException('Browser module entry keys must be unique and non-empty.');
        }

        $this->surface = $surface;
        $this->entries = array_values($entries);
    }

    public function toArray(): array
    {
        return [
            'contractVersion' => self::CONTRACT_VERSION,
            'surface' => $this->surface,
            'entries' => array_map(static fn(BrowserModuleEntry $entry): array => $entry->toArray(), $this->entries),
        ];
    }

    public function getFieldEntryKeys(string $fieldUid): array
    {
        $keys = [];

        foreach ($this->entries as $entry) {
            foreach ($entry->targets as $target) {
                if (($target['type'] ?? null) === 'field' && ($target['uid'] ?? null) === $fieldUid) {
                    $keys[] = $entry->key;
                    break;
                }
            }
        }

        return $keys;
    }
}
