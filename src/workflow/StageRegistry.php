<?php
namespace verbb\formie\workflow;

class StageRegistry
{
    // Properties
    // =========================================================================

    private array $stages = [];


    // Public Methods
    // =========================================================================

    public function __construct(array $stages = [])
    {
        foreach ($stages as $stage) {
            $this->register($stage);
        }
    }

    public function register(StageInterface $stage): void
    {
        $this->stages[] = $stage;
    }

    public function has(string $stageName): bool
    {
        foreach ($this->stages as $stage) {
            if ($stage->getName() === $stageName) {
                return true;
            }
        }

        return false;
    }

    public function insertBefore(string $anchorStageName, StageInterface $stage): bool
    {
        foreach ($this->stages as $index => $existingStage) {
            if ($existingStage->getName() === $anchorStageName) {
                // Extension stages usually need to hook relative to an existing
                // lifecycle boundary, not by rebuilding the whole registry.
                array_splice($this->stages, $index, 0, [$stage]);

                return true;
            }
        }

        return false;
    }

    public function insertAfter(string $anchorStageName, StageInterface $stage): bool
    {
        foreach ($this->stages as $index => $existingStage) {
            if ($existingStage->getName() === $anchorStageName) {
                array_splice($this->stages, $index + 1, 0, [$stage]);

                return true;
            }
        }

        return false;
    }

    public function all(): array
    {
        return $this->stages;
    }
}
