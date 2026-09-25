<?php
namespace verbb\formie\workflow;

use verbb\formie\enums\SubmissionOperation;
use verbb\formie\workflow\tasks\TaskInterface;

use InvalidArgumentException;

final class TaskDefinition
{
    // Public Methods
    // =========================================================================

    public function __construct(
        public readonly string $id,
        public readonly TaskInterface $handler,
        public readonly array $operations,
        public readonly bool $publicAnchor = true,
        public readonly bool $visitorProgression = false,
    ) {
        if ($id === '' || !$operations) {
            throw new InvalidArgumentException('Tasks require a unique ID and explicit operations.');
        }

        foreach ($operations as $operation) {
            if (!$operation instanceof SubmissionOperation) {
                throw new InvalidArgumentException('Task operations must be SubmissionOperation cases.');
            }
        }
    }
}
