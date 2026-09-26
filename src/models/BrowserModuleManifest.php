<?php
namespace verbb\formie\models;

use InvalidArgumentException;

class BrowserModuleManifest
{
    // Constants
    // =========================================================================

    public const CONTRACT_VERSION = 1;


    // Properties
    // =========================================================================

    public readonly array $entries;


    // Public Methods
    // =========================================================================

    public function __construct(array $entries = [], int $contractVersion = self::CONTRACT_VERSION)
    {
        if ($contractVersion !== self::CONTRACT_VERSION) {
            throw new InvalidArgumentException('Unsupported browser module contractVersion. Update Formie and its browser packages together.');
        }

        $entries = array_map(static fn(array $entry): array => (new BrowserModuleEntry($entry))->toArray(), $entries);
        $keys = array_column($entries, 'key');
        if (count(array_unique($keys)) !== count($entries) || in_array('', $keys, true)) {
            throw new InvalidArgumentException('Browser module entry keys must be unique and non-empty.');
        }

        $this->entries = $entries;
    }

    public function toArray(): array
    {
        return ['contractVersion' => self::CONTRACT_VERSION, 'entries' => $this->entries];
    }
}
