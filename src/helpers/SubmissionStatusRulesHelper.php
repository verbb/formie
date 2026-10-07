<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\enums\NavigationIntent;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\models\FormSettings;
use verbb\formie\models\SubmissionCommand;

class SubmissionStatusRulesHelper
{
    // Static Methods
    // =========================================================================

    public static function applyRules(Form $form, Submission $submission, SubmissionCommand $command, ?bool $hasNextPage): void
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

            if (!self::_shouldApplyTrigger($trigger, $command, $hasNextPage)) {
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

            if ($status = Formie::$plugin->getSubmissionStatuses()->getStatusById($statusId)) {
                if (Formie::$plugin->getFormGroupPolicy()->isStatusAllowed($form, (int)$status->id)) {
                    $submission->setStatus($status);
                }
            }

            return;
        }
    }

    private static function _shouldApplyTrigger(string $trigger, SubmissionCommand $command, ?bool $hasNextPage): bool
    {
        if ($command->operation !== SubmissionOperation::SUBMIT) {
            return false;
        }

        if ($command->navigation === NavigationIntent::BACK) {
            return false;
        }

        if ($trigger === 'everyPage') {
            return in_array($command->navigation, [
                NavigationIntent::ADVANCE,
                NavigationIntent::STAY,
            ], true);
        }

        if ($command->navigation !== NavigationIntent::ADVANCE) {
            return false;
        }

        return $hasNextPage !== true;
    }
}
