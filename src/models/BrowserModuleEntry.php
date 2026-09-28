<?php
namespace verbb\formie\models;

/** One configured manifest entry. Executable code is resolved only by a trusted registry. */
final readonly class BrowserModuleEntry
{
    // Properties
    // =========================================================================

    public string $key;
    public string $moduleId;
    public string $kind;
    public array $targets;
    public array $config;
    public bool $required;


    // Public Methods
    // =========================================================================

    public function __construct(string $key, string $moduleId, string $kind, array $targets, array $config = [], bool $required = true)
    {
        if ($key === '') {
            throw new \InvalidArgumentException('Browser module entry keys must be non-empty.');
        }

        BrowserModule::validateModuleId($moduleId);
        BrowserModule::validateKind($kind);
        BrowserModule::validateTargets($targets);

        $this->key = $key;
        $this->moduleId = $moduleId;
        $this->kind = $kind;
        $this->targets = array_values($targets);
        $this->config = $config;
        $this->required = $required;
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'moduleId' => $this->moduleId,
            'kind' => $this->kind,
            'targets' => $this->targets,
            'config' => $this->config === [] ? new \stdClass() : $this->config,
            'required' => $this->required,
        ];
    }
}
