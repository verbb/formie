<?php
namespace verbb\formie\fields\definitions;

/**
 * Describes the reference selectors and nested-reference policy a field exposes.
 */
final class FieldReferences
{
    // Static Methods
    // =========================================================================

    public static function make(): self
    {
        return new self();
    }


    // Properties
    // =========================================================================

    public readonly bool $allowPrimary;
    public readonly ?string $primaryCondition;
    public readonly ?string $primaryTokenSuffix;
    public readonly bool $allowNested;
    public readonly string $nestedMode;
    public readonly array $selectors;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        $this->allowPrimary = $config['allowPrimary'] ?? true;
        $this->primaryCondition = $config['primaryCondition'] ?? null;
        $this->primaryTokenSuffix = $config['primaryTokenSuffix'] ?? null;
        $this->allowNested = $config['allowNested'] ?? false;
        $this->nestedMode = $config['nestedMode'] ?? 'none';
        $this->selectors = $config['selectors'] ?? [];
    }

    public function toConfigArray(): array
    {
        return [
            'allowPrimary' => $this->allowPrimary,
            'primaryCondition' => $this->primaryCondition,
            'primaryTokenSuffix' => $this->primaryTokenSuffix,
            'allowNested' => $this->allowNested,
            'nestedMode' => $this->nestedMode,
            'selectors' => array_values(array_map(static function(FieldReferenceSelector $selector) {
                return $selector->toArray();
            }, $this->selectors)),
        ];
    }
}
