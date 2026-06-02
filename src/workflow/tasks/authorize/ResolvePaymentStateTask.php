<?php
namespace verbb\formie\workflow\tasks\authorize;

use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\services\SubmissionWorkflow;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;
use verbb\formie\fields as formiefields;

class ResolvePaymentStateTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function getStage(): string
    {
        return Stage::AUTHORIZE->value;
    }

    public function getName(): string
    {
        return Task::AUTHORIZE_RESOLVE_PAYMENT_STATE->value;
    }

    public function execute(WorkflowContext $context): TaskResult
    {
        // Payment handling is final-submit only. Intermediate page submits should
        // never mark payment as required.
        if ($context->request->submitAction === SubmissionWorkflow::SUBMIT_ACTION_SUBMIT && $context->nextPage) {
            $context->taskState['payment.required'] = false;

            return TaskResult::continue();
        }

        $submission = $context->request->submission;
        $hasPaymentRequirement = false;

        foreach ($submission->getFields() as $field) {
            if (!$field instanceof formiefields\Payment) {
                continue;
            }

            if ($field->isConditionallyHidden($submission) || $field->getIsDisabled()) {
                continue;
            }

            if ($field->getPaymentIntegration()) {
                $hasPaymentRequirement = true;
                break;
            }
        }

        $context->taskState['payment.required'] = $hasPaymentRequirement;

        return TaskResult::continue();
    }
}
