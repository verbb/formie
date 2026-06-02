<?php
namespace verbb\formie\workflow;

use verbb\formie\models\SubmissionRequest;

use craft\base\Model;

class WorkflowPolicy extends Model
{
    // Static Methods
    // =========================================================================

    public static function fromTasks(SubmissionRequest $request, array $enabledTasks): self
    {
        // Process modes select behavior by enabling/disabling tasks inside the
        // canonical stage pipeline, rather than by swapping in a separate
        // workflow implementation for each transport or submit mode.
        return new self([
            'request' => $request,
            'enabledTasks' => array_values(array_unique($enabledTasks)),
        ]);
    }


    // Properties
    // =========================================================================

    public SubmissionRequest $request;
    public array $enabledTasks = [];


    // Public Methods
    // =========================================================================

    public function isTaskEnabled(string $taskName): bool
    {
        return in_array($taskName, $this->enabledTasks, true);
    }
}
