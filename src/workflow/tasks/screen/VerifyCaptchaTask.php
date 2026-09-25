<?php
namespace verbb\formie\workflow\tasks\screen;

use verbb\formie\Formie;
use verbb\formie\enums\NavigationIntent;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;
use verbb\formie\workflow\WorkflowContext;

use Craft;

class VerifyCaptchaTask implements TaskInterface
{
    // Public Methods
    // =========================================================================

    public function execute(WorkflowContext $context): TaskResult
    {
        $request = $context->command;

        if ($request->navigation !== NavigationIntent::ADVANCE) {
            return TaskResult::continue();
        }

        if ($request->submission->isSpam) {
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
