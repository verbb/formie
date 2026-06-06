<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\FormSettings;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\services\SubmissionWorkflow;

class SubmissionStatusRulesHelper
{
    // Public Methods
    // =========================================================================

    public static function applyRules(Form $form, Submission $submission, SubmissionRequest $request, ?bool $hasNextPage): void
    {
        $settings = $form->getSettings();

        if (!$settings instanceof FormSettings || !$settings->enableStatusRules) {
            return;
        }

        $rules = $settings->statusRules ?? [];

        if (!$rules) {
            return;
        }

        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $trigger = (string)($rule['trigger'] ?? 'finalSubmit');

            if (!self::_shouldApplyTrigger($trigger, $request, $hasNextPage)) {
                continue;
            }

            if (!empty($rule['enableConditions'])) {
                $conditionSettings = $rule['conditions'] ?? [];

                if (!$conditionSettings || !ConditionsHelper::getConditionalTestResult($conditionSettings, $submission)) {
                    continue;
                }
            }

            $statusId = (int)($rule['statusId'] ?? 0);

            if (!$statusId) {
                continue;
            }

            if ($status = Formie::$plugin->getStatuses()->getStatusById($statusId)) {
                $submission->setStatus($status);
            }

            return;
        }
    }


    // Private Methods
    // =========================================================================

    private static function _shouldApplyTrigger(string $trigger, SubmissionRequest $request, ?bool $hasNextPage): bool
    {
        if ($request->processMode !== SubmissionWorkflow::PROCESS_MODE_SUBMIT) {
            return false;
        }

        if ($request->submitAction === SubmissionWorkflow::SUBMIT_ACTION_BACK) {
            return false;
        }

        if ($trigger === 'everyPage') {
            return in_array($request->submitAction, [
                SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
                SubmissionWorkflow::SUBMIT_ACTION_SAVE,
            ], true);
        }

        if ($request->submitAction !== SubmissionWorkflow::SUBMIT_ACTION_SUBMIT) {
            return false;
        }

        return $hasNextPage !== true;
    }
}
