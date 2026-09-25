<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\FormSettings;
use verbb\formie\models\SubmissionCommand;
use verbb\formie\services\SubmissionWorkflow;

class SubmissionStatusRulesHelper
{
    // Public Methods
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


    // Private Methods
    // =========================================================================

    private static function _shouldApplyTrigger(string $trigger, SubmissionCommand $command, ?bool $hasNextPage): bool
    {
        if ($command->operation !== \verbb\formie\enums\SubmissionOperation::SUBMIT) {
            return false;
        }

        if ($command->navigation === \verbb\formie\enums\NavigationIntent::BACK) {
            return false;
        }

        if ($trigger === 'everyPage') {
            return in_array($command->navigation, [
                \verbb\formie\enums\NavigationIntent::ADVANCE,
                \verbb\formie\enums\NavigationIntent::STAY,
            ], true);
        }

        if ($command->navigation !== \verbb\formie\enums\NavigationIntent::ADVANCE) {
            return false;
        }

        return $hasNextPage !== true;
    }
}
