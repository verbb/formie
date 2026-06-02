<?php
namespace verbb\formie\workflow\tasks\finalize;

use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\Formie;
use verbb\formie\models\Settings;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;

use Craft;

class ApplySpamBehaviourTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function getStage(): string
    {
        return Stage::FINALIZE->value;
    }

    public function getName(): string
    {
        return Task::FINALIZE_APPLY_SPAM_BEHAVIOUR->value;
    }

    public function execute(WorkflowContext $context): TaskResult
    {
        $submission = $context->request->submission;
        $settings = Formie::$plugin->getSettings();

        if ($submission->isSpam && $settings->spamBehaviour === Settings::SPAM_BEHAVIOUR_SUCCESS && !$submission->hasErrors()) {
            // Spam should still be persisted/marked internally, but outwardly behave
            // like a successful submission when the plugin setting requests it.
            $context->processingSuccess = true;
        }

        if ($submission->isSpam && $settings->spamBehaviour === Settings::SPAM_BEHAVIOUR_MESSAGE) {
            $errorMessage = $settings->spamBehaviourMessage ?: $submission->spamReason ?: Craft::t('formie', 'Your submission has been flagged as spam.');
            $submission->addError('form', $errorMessage);
            $context->processingSuccess = false;
        }

        if (!$context->processingSuccess) {
            return TaskResult::halt(false, ['reason' => 'finalizeFailed']);
        }

        return TaskResult::continue();
    }
}
