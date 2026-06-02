<?php
namespace verbb\formie\migrations\plugins;

class MigrationResult
{
    // Properties
    // =========================================================================

    public bool $ok = true;
    public array $lines = [];
    public array $stats = [];


    // Public Methods
    // =========================================================================

    public function addLine(MigrationLine $line): void
    {
        $this->lines[] = $line;
    }

    public function setStat(string $key, mixed $value): void
    {
        $this->stats[$key] = $value;
    }

    public function incrementStat(string $key, int $value = 1): void
    {
        $this->stats[$key] = (int)($this->stats[$key] ?? 0) + $value;
    }
}

