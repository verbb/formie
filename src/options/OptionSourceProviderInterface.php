<?php
namespace verbb\formie\options;

interface OptionSourceProviderInterface
{
    // Static Methods
    // =========================================================================

    public static function handle(): string;
    public static function displayName(): string;
    public static function usages(): array;


    // Public Methods
    // =========================================================================

    public function getBuilderConfig(array $params = []): array;
    public function resolveOptions(array $params, OptionSourceContext $context): OptionList;
}
