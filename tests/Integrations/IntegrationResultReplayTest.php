<?php

use verbb\formie\Formie;
use verbb\formie\base\Messaging;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\DeliveryAttempt;
use verbb\formie\models\IntegrationDispatchPlan;
use verbb\formie\models\IntegrationExecutionContext;
use verbb\formie\models\IntegrationResult;
use verbb\formie\services\IntegrationRunner;

class ResultReplayIntegration extends Messaging
{
    public static int $calls = 0;
    public static bool $eligible = true;
    public static bool $validSettings = true;

    public static function displayName(): string { return 'Result replay fixture'; }
    public static function supportsConnection(): bool { return false; }
    public function shouldTrigger(Submission $submission, array $context = []): bool { return self::$eligible; }
    public function validate($attributeNames = null, $clearErrors = true): bool { return self::$validSettings && parent::validate($attributeNames, $clearErrors); }
    protected function executePayload(Submission $submission): IntegrationResult
    {
        self::$calls++;
        return IntegrationResult::succeeded('remote-record')->withOutputs(['reference' => 'accepted-record']);
    }
}

it('replays the durable result through every early delivery branch', function (string $branch) {
    ResultReplayIntegration::$calls = 0;
    ResultReplayIntegration::$eligible = ResultReplayIntegration::$validSettings = true;
    $form = formie()->form()->singleLineTextField('message')->create();
    $submission = formie()->submission($form)->save();
    $handle = 'resultReplay' . uniqid();
    $connection = new ResultReplayIntegration(['name' => 'Result replay', 'handle' => $handle, 'enabled' => true]);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($connection, false))->toBeTrue();
    $form->settings->integrations = [$handle => ['enabled' => true]];
    $runner = Formie::$plugin->getIntegrationRunner();
    $run = 'replay-' . uniqid();
    $first = $runner->runSteps($submission, [$handle], [], null, $run)->results()[0]['result'];
    expect($first->isSuccessful())->toBeTrue();
    $reported = [];
    $skipped = [];
    $onResult = function ($event) use (&$reported) { $reported[] = $event->result; };
    $onSkipped = function ($event) use (&$skipped) { $skipped[] = $event->result; };
    $runner->on(IntegrationRunner::EVENT_RESULT, $onResult);
    $runner->on(IntegrationRunner::EVENT_SKIPPED, $onSkipped);

    try {
        if ($branch === 'disabled') $form->settings->integrations = [$handle => ['enabled' => false]];
        if ($branch === 'conditions') ResultReplayIntegration::$eligible = false;
        if ($branch === 'settings') ResultReplayIntegration::$validSettings = false;
        if ($branch === 'legacy') (new DeliveryAttempt($submission->id, 'integration:' . $handle, $run))->execute([], fn() => true);
        $batch = $runner->runSteps($submission, [$handle], [], null, $run);
        $uid = Formie::$plugin->getDeliveryAttempts()->prepare(new IntegrationExecutionContext($submission->id, $form->id, $handle, $run), 'integration');
        $projection = Formie::$plugin->getIntegrationDispatcher()->loadContext($submission, $run)->getResult($handle);

        expect($batch->results()[0]['result']->toStorage())->toBe($first->toStorage())
            ->and($reported)->toHaveCount(1)
            ->and($reported[0]->toStorage())->toBe($first->toStorage())
            ->and($skipped)->toBe([])
            ->and($projection['status'])->toBe('succeeded')
            ->and($projection['outputs']['reference'])->toBe('accepted-record')
            ->and(Formie::$plugin->getDeliveryAttempts()->get($uid)['status'])->toBe('succeeded')
            ->and(ResultReplayIntegration::$calls)->toBe(1);
    } finally {
        $runner->off(IntegrationRunner::EVENT_RESULT, $onResult);
        $runner->off(IntegrationRunner::EVENT_SKIPPED, $onSkipped);
        ResultReplayIntegration::$eligible = ResultReplayIntegration::$validSettings = true;
    }
})->with(['disabled', 'conditions', 'settings', 'legacy']);

it('keeps the failure policy when replaying a failed disabled binding', function () {
    $form = formie()->form()->create();
    $submission = formie()->submission($form)->save();
    $run = 'failed-replay';
    $attempts = Formie::$plugin->getDeliveryAttempts();
    $uid = $attempts->prepare(new IntegrationExecutionContext($submission->id, $form->id, 'disabled', $run), 'integration');
    $attempts->execute($uid, fn() => IntegrationResult::failed('permanent_failure'));
    $batch = Formie::$plugin->getIntegrationRunner()->runSteps($submission, ['disabled', 'following'], [], new IntegrationDispatchPlan(['failurePolicy' => 'stop']), $run);
    expect($batch->stoppedOnFailure())->toBeTrue()
        ->and($batch->accepts())->toBeFalse()
        ->and($batch->results()[0]['result']->code)->toBe('permanent_failure')
        ->and($batch->results()[1]['result']->code)->toBe('previous_step_failed');
});
