<?php
namespace verbb\formie\events;

use verbb\formie\Formie;
use verbb\formie\workflow\StageRegistry;
use verbb\formie\workflow\StageInterface;

use yii\base\Event;

class RegisterWorkflowStagesEvent extends Event
{
    // Properties
    // =========================================================================

    public array $stages = [];
    

    // Public Methods
    // =========================================================================

    public function hasStage(string $stageName): bool
    {
        $registry = new StageRegistry($this->stages);

        return $registry->has($stageName);
    }

    public function insertStageBefore(string $anchorStageName, StageInterface $stage): bool
    {
        $registry = new StageRegistry($this->stages);

        if (!$registry->insertBefore($anchorStageName, $stage)) {
            Formie::warning('Unable to insert workflow stage "{stage}" before "{anchor}" because anchor stage was not found.', [
                'stage' => $stage->getName(),
                'anchor' => $anchorStageName,
            ]);

            return false;
        }

        $this->stages = $registry->all();

        return true;
    }

    public function insertStageAfter(string $anchorStageName, StageInterface $stage): bool
    {
        $registry = new StageRegistry($this->stages);

        if (!$registry->insertAfter($anchorStageName, $stage)) {
            Formie::warning('Unable to insert workflow stage "{stage}" after "{anchor}" because anchor stage was not found.', [
                'stage' => $stage->getName(),
                'anchor' => $anchorStageName,
            ]);

            return false;
        }

        $this->stages = $registry->all();

        return true;
    }
}
