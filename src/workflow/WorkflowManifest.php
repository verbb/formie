<?php
namespace verbb\formie\workflow;

use verbb\formie\enums\NavigationIntent;
use verbb\formie\enums\SubmissionOperation as Operation;
use verbb\formie\enums\SubmissionPolicy;
use verbb\formie\enums\workflow\Stage;
use verbb\formie\models\SubmissionCommand;
use verbb\formie\workflow\tasks\dispatch\RevisionFollowUpsTask;
use verbb\formie\workflow\tasks\dispatch\SendNotificationsTask;
use verbb\formie\workflow\tasks\dispatch\SendSpamNotificationsTask;
use verbb\formie\workflow\tasks\dispatch\TriggerIntegrationsTask;
use verbb\formie\workflow\tasks\finalize\FinalizeTask;
use verbb\formie\workflow\tasks\persist\PersistSubmissionTask;
use verbb\formie\workflow\tasks\persist\PlanTask;
use verbb\formie\workflow\tasks\persist\ProcessPaymentTask;
use verbb\formie\workflow\tasks\persist\QuestionnaireResultTask;
use verbb\formie\workflow\tasks\preflight\ApplyStatusRulesTask;
use verbb\formie\workflow\tasks\preflight\ApplySubmissionDefaultsTask;
use verbb\formie\workflow\tasks\preflight\CaptureMetadataTask;
use verbb\formie\workflow\tasks\preflight\ClearHiddenValuesTask;
use verbb\formie\workflow\tasks\preflight\EnforceProgressionTask;
use verbb\formie\workflow\tasks\preflight\ResolveNavigationIntentTask;
use verbb\formie\workflow\tasks\preflight\ResolveTransitionTask;
use verbb\formie\workflow\tasks\screen\EvaluateSpamTask;
use verbb\formie\workflow\tasks\screen\VerifyCaptchaTask;
use verbb\formie\workflow\tasks\validate\ValidateSubmissionTask;

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
                new TaskDefinition('preflight.resolveNavigationIntent', new ResolveNavigationIntentTask(), $journey, false, true),
                new TaskDefinition('preflight.applySubmissionDefaults', new ApplySubmissionDefaultsTask(), $writes),
                new TaskDefinition('preflight.clearHiddenValues', new ClearHiddenValuesTask(), $writes),
                new TaskDefinition('preflight.enforceProgression', new EnforceProgressionTask(), $submit, false, true),
                new TaskDefinition('preflight.resolveTransition', new ResolveTransitionTask(), $journey, true, true),
                new TaskDefinition('preflight.captureMetadata', new CaptureMetadataTask(), $writes),
                new TaskDefinition('preflight.applyStatusRules', new ApplyStatusRulesTask(), $submit, true, true),
            ],
            Stage::VALIDATE->value => [
                new TaskDefinition('validate.submission', new ValidateSubmissionTask(), [Operation::SUBMIT, Operation::REVISE]),
            ],
            Stage::SCREEN->value => [
                new TaskDefinition('screen.evaluateSpam', new EvaluateSpamTask(), $submit),
                new TaskDefinition('screen.verifyCaptcha', new VerifyCaptchaTask(), $submit),
            ],
            Stage::PERSIST->value => [
                new TaskDefinition('persist.plan', new PlanTask(), $all, false),
                new TaskDefinition('persist.submission', new PersistSubmissionTask(), $writes),
                new TaskDefinition('persist.processPayment', new ProcessPaymentTask(), $completion),
                new TaskDefinition('persist.questionnaireResult', new QuestionnaireResultTask(), [Operation::SUBMIT, Operation::REVISE, Operation::PAYMENT_REPLAY]),
            ],
            Stage::DISPATCH->value => [
                new TaskDefinition('dispatch.sendNotifications', new SendNotificationsTask(), $completion),
                new TaskDefinition('dispatch.revision', new RevisionFollowUpsTask(), [Operation::REVISE], false),
                new TaskDefinition('dispatch.triggerIntegrations', new TriggerIntegrationsTask(), [Operation::SUBMIT, Operation::REVISE, Operation::PAYMENT_REPLAY]),
                new TaskDefinition('dispatch.sendSpamNotifications', new SendSpamNotificationsTask(), $completion),
            ],
            Stage::FINALIZE->value => [
                new TaskDefinition('finalize.internal', new FinalizeTask(), $all, false),
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
