<?php
namespace verbb\formie\workflow\tasks\screen;

use verbb\formie\Formie;
use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\services\SubmissionWorkflow;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;

use Craft;

class RunCaptchaChecksTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function getStage(): string
    {
        return Stage::SCREEN->value;
    }

    public function getName(): string
    {
        return Task::SCREEN_RUN_CAPTCHA_CHECKS->value;
    }

    public function execute(WorkflowContext $context): TaskResult
    {
        $request = $context->request;

        if ($request->submitAction !== SubmissionWorkflow::SUBMIT_ACTION_SUBMIT) {
            return TaskResult::continue();
        }

        $captchas = Formie::$plugin->getIntegrations()->getAllEnabledCaptchasForForm($request->form);

        foreach ($captchas as $captcha) {
            if (!$captcha->runValidation($request->submission)) {
                if ($captcha->validationErrored) {
                    continue;
                }

                $request->submission->isSpam = true;
                $request->submission->spamReason = Craft::t('formie', 'Failed Captcha “{c}”: “{m}”', [
                    'c' => $captcha::displayName(),
                    'm' => $captcha->spamReason,
                ]);
                $request->submission->spamClass = get_class($captcha);
            }
        }

        return TaskResult::continue();
    }
}
