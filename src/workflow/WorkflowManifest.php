<?php
namespace verbb\formie\workflow;

use verbb\formie\enums\NavigationIntent;
use verbb\formie\enums\SubmissionOperation as Operation;
use verbb\formie\enums\SubmissionPolicy;
use verbb\formie\enums\workflow\Stage;
use verbb\formie\models\SubmissionCommand;
use verbb\formie\workflow\tasks\dispatch;
use verbb\formie\workflow\tasks\finalize;
use verbb\formie\workflow\tasks\persist;
use verbb\formie\workflow\tasks\preflight;
use verbb\formie\workflow\tasks\screen;
use verbb\formie\workflow\tasks\validate;

/** The sole stage order, built-in task order, operation policy and anchor catalogue. */
final class WorkflowManifest
{
    // Static Methods
    // =========================================================================

    public static function stages(): array
    {
        $submit = [Operation::SUBMIT];
        $journey = [Operation::SUBMIT, Operation::SAVE_DRAFT];
        $writes = [Operation::SUBMIT, Operation::SAVE_DRAFT, Operation::REVISE];
        $completion = [Operation::SUBMIT, Operation::PAYMENT_REPLAY];
        $all = Operation::cases();

        return [
            Stage::PREFLIGHT->value => [
                new TaskDefinition('preflight.resolveNavigationIntent', new preflight\ResolveNavigationIntentTask(), $journey, true, true),
                new TaskDefinition('preflight.applySubmissionDefaults', new preflight\ApplySubmissionDefaultsTask(), $writes),
                new TaskDefinition('preflight.clearHiddenValues', new preflight\ClearHiddenValuesTask(), $writes),
                new TaskDefinition('preflight.resolveTransition', new preflight\ResolveTransitionTask(), $journey, true, true),
                new TaskDefinition('preflight.captureMetadata', new preflight\CaptureMetadataTask(), $writes),
                new TaskDefinition('preflight.applyStatusRules', new preflight\ApplyStatusRulesTask(), $submit, true, true),
            ],
            Stage::VALIDATE->value => [
                new TaskDefinition('validate.submission', new validate\ValidateSubmissionTask(), [Operation::SUBMIT, Operation::REVISE]),
                new TaskDefinition('validate.enforceProgression', new preflight\EnforceProgressionTask(), $submit, true, true),
                new TaskDefinition('validate.resolveTransition', new preflight\ResolveTransitionTask(), $submit, true, true),

            ],
            Stage::SCREEN->value => [
                new TaskDefinition('screen.evaluateSpam', new screen\EvaluateSpamTask(), $submit),
                new TaskDefinition('screen.verifyCaptcha', new screen\VerifyCaptchaTask(), $submit),
            ],
            Stage::PERSIST->value => [
                new TaskDefinition('persist.plan', new persist\PlanTask(), $all, false),
                new TaskDefinition('persist.submission', new persist\PersistSubmissionTask(), $writes),
                new TaskDefinition('persist.processPayment', new persist\ProcessPaymentTask(), $completion),
                new TaskDefinition('persist.questionnaireResult', new persist\QuestionnaireResultTask(), [Operation::SUBMIT, Operation::REVISE, Operation::PAYMENT_REPLAY]),
            ],
            Stage::DISPATCH->value => [
                new TaskDefinition('dispatch.sendNotifications', new dispatch\SendNotificationsTask(), $completion),
                new TaskDefinition('dispatch.revision', new dispatch\RevisionFollowUpsTask(), [Operation::REVISE], false),
                new TaskDefinition('dispatch.triggerIntegrations', new dispatch\TriggerIntegrationsTask(), [Operation::SUBMIT, Operation::REVISE, Operation::PAYMENT_REPLAY]),
                new TaskDefinition('dispatch.sendSpamNotifications', new dispatch\SendSpamNotificationsTask(), $completion),
            ],
            Stage::FINALIZE->value => [
                new TaskDefinition('finalize.internal', new finalize\FinalizeTask(), $all, false),
            ],
        ];
    }

    public static function applies(TaskDefinition $task, Stage $stage, SubmissionCommand $command): bool
    {
        if (!in_array($command->operation, $task->operations, true)) {
            return false;
        }

        if ($task->visitorProgression && !$command->usesVisitorProgression()) {
            return false;
        }

        if ($task->id === 'preflight.resolveTransition' && !in_array($command->navigation, [NavigationIntent::BACK, NavigationIntent::STAY], true)) {
            return false;
        }
        if (in_array($task->id, ['validate.enforceProgression', 'validate.resolveTransition'], true) && in_array($command->navigation, [NavigationIntent::BACK, NavigationIntent::STAY], true)) {
            return false;
        }

        if ($task->id === 'validate.submission' && $command->navigation === NavigationIntent::BACK) {
            return false;
        }

        if ($command->policy === SubmissionPolicy::ADMINISTRATIVE_CREATE &&
            (in_array($stage, [Stage::SCREEN, Stage::DISPATCH], true) || $task->id === 'persist.processPayment')) {
            return false;
        }

        if ($stage === Stage::SCREEN && in_array($command->navigation, [NavigationIntent::BACK, NavigationIntent::STAY], true)) {
            return false;
        }

        return true;
    }
}
