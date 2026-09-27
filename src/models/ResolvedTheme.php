<?php
namespace verbb\formie\models;

use verbb\formie\deprecations\ThemeConfigLegacyKeys;

final class ResolvedTheme
{
    // Public Methods
    // =========================================================================

    public function __construct(
        public readonly string $mode,
        public readonly array $config,
        public readonly array $browserClassMap,
        public readonly string $digest,
        public readonly bool $allowsRawHtml,
    ) {
    }

    public function getConfigItem(string $key): array|bool|null
    {
        return ThemeConfigLegacyKeys::getMergedThemeConfigItem($this->config, __METHOD__, $key);
    }

    public function isNone(): bool
    {
        return $this->mode === 'none';
    }

    public function toFragmentState(): array
    {
        return [
            'mode' => $this->mode,
            'config' => $this->config,
            'digest' => $this->digest,
            'allowsRawHtml' => $this->allowsRawHtml,
        ];
    }
}
