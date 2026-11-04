<?php

use craft\db\Query;
use verbb\formie\Formie;
use verbb\formie\base\HelpDesk;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationResult;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\events\ModifyFormIntegrationsEvent;
use verbb\formie\services\Integrations;
use yii\base\Event;

class PreflightRetryProvider extends HelpDesk
{
    public static string $failure = '';
    public static int $writes = 0;
    public static function displayName(): string { return 'Preflight retry'; }
    public function fetchConfig(): IntegrationConfig { return new IntegrationConfig(); }

    protected function executePayload(Submission $submission): IntegrationResult
    {
        if (self::$failure === 'mapping') {
            throw new TypeError('A structured field mapping was passed to a string parameter.');
        }
        if (self::$failure === 'false') {
            return $this->resultForPayload(false);
        }
        if (self::$failure === 'handled') {
            $this->handleSubmissionApiError(new TypeError('Mapping failed'), $submission);
            return $this->resultForPayload(false);
        }
        $this->executeDeliveryWrite('POST', 'https://example.com/records', ['json' => ['name' => 'Test']], function() {
            self::$writes++;
            if (self::$failure === 'transport') {
                throw new RuntimeException('The response was lost.');
            }
            return ['id' => 'record-1'];
        });
        if (self::$failure === 'after-write') {
            throw new TypeError('Local processing failed after the write.');
        }
        return $this->resultForPayload(true);
    }
}

it('runs third-party native-style providers again without needing them to track their writes', function (): void {
    $form = formie()->form()->singleLineTextField('fullName')->create();
    $submission = formie()->submission($form)->save();
    $integration = new class(['name' => 'Custom SDK', 'handle' => 'customSdk']) extends HelpDesk {
        public static int $writes = 0;
        public static function displayName(): string { return 'Custom SDK'; }
        protected function executePayload(Submission $submission): IntegrationResult {
            self::$writes++;
            throw new TypeError('An SDK sent successfully, but processing its result failed.');
        }
    };
    $runner = Formie::$plugin->getIntegrationRunner();
    expect($integration->supportsDeliveryWriteTracking())->toBeFalse();
    expect($runner->runIntegration($integration, $submission, 'custom-sdk', 'queued')->status->value)->toBe('failed');
    expect($runner->runIntegration($integration, $submission, 'custom-sdk', 'queued')->status->value)->toBe('failed');
    expect($integration::$writes)->toBe(2);
});

it('allows queued retries and manual reruns after a failure before sending', function (string $failure, string $code, string $retry): void {
    $form = formie()->form()->singleLineTextField('fullName')->create();
    $submission = formie()->submission($form)->save();
    $integration = new PreflightRetryProvider(['name' => 'Preflight retry', 'handle' => 'preflightRetry', 'enabled' => true]);
    $handler = function (ModifyFormIntegrationsEvent $event) use ($form, $integration): void {
        if ($event->form->id === $form->id) $event->integrations[] = $integration;
    };
    Event::on(Integrations::class, Integrations::EVENT_MODIFY_FORM_INTEGRATIONS, $handler);
    PreflightRetryProvider::$failure = $failure;
    PreflightRetryProvider::$writes = 0;
    try {
        $runner = Formie::$plugin->getIntegrationRunner();
        $runner->queueSteps($submission, [$integration->handle], \verbb\formie\enums\SubmissionOperation::SUBMIT, ['triggerEvent' => 'submit'], executionKey: 'preflight-job');
        $attempts = Formie::$plugin->getDeliveryAttempts();
        $uid = (new Query())->select('uid')->from($attempts::TABLE)->where(['submissionId' => $submission->id, 'step' => 'dispatch'])->scalar();
        $result = $runner->runQueuedAttempt($uid);
        expect($result->status->value)->toBe('failed')->and($result->retryable)->toBeTrue()->and(PreflightRetryProvider::$writes)->toBe(0);
        $child = (new Query())->from($attempts::TABLE)->where(['submissionId' => $submission->id, 'step' => 'integration'])->one();
        expect(json_decode($child['result'], true)['code'])->toBe($code);
        PreflightRetryProvider::$failure = '';
        $result = $retry === 'queue' ? $runner->runQueuedAttempt($uid) : Formie::$plugin->getIntegrationTriggers()->runManualIntegration($integration, $submission);
        expect($result->status->value)->toBe('succeeded')->and(PreflightRetryProvider::$writes)->toBe(1);
        expect($attempts->get($child['uid']))->not->toBeEmpty();
        if ($retry === 'queue') {
            expect($runner->runQueuedAttempt($uid)->status->value)->toBe('succeeded')->and(PreflightRetryProvider::$writes)->toBe(1);
        }
    } finally {
        Event::off(Integrations::class, Integrations::EVENT_MODIFY_FORM_INTEGRATIONS, $handler);
    }
})->with([['mapping', 'delivery_failed'], ['false', 'legacy_failure'], ['handled', 'delivery_failed']])->with(['queue', 'manual']);

it('repeats only a write that did not complete when a failed delivery runs again', function (string $failure, int $writes): void {
    $form = formie()->form()->singleLineTextField('fullName')->create();
    $submission = formie()->submission($form)->save();
    $integration = new PreflightRetryProvider(['name' => 'Preflight retry', 'handle' => 'preflightRetry']);
    PreflightRetryProvider::$failure = $failure;
    PreflightRetryProvider::$writes = 0;
    $runner = Formie::$plugin->getIntegrationRunner();
    expect($runner->runIntegration($integration, $submission, 'later-error', 'queued')->status->value)->toBe('failed');
    expect(PreflightRetryProvider::$writes)->toBe(1);
    PreflightRetryProvider::$failure = '';
    expect($runner->runIntegration($integration, $submission, 'later-error', 'queued')->status->value)->toBe('succeeded');
    expect(PreflightRetryProvider::$writes)->toBe($writes);
    expect(Formie::$plugin->getIntegrationTriggers()->runManualIntegration($integration, $submission)->status->value)->toBe('succeeded');
    expect(PreflightRetryProvider::$writes)->toBe($writes + 1);
})->with([['transport', 2], ['after-write', 1]]);

it('retries a delivery left unknown by an earlier release through its operations without deleting history', function (): void {
    $form = formie()->form()->singleLineTextField('fullName')->create();
    $submission = formie()->submission($form)->save();
    $integration = new PreflightRetryProvider(['name' => 'Recovery', 'handle' => 'preflightRecovery', 'enabled' => true]);
    $handler = function (ModifyFormIntegrationsEvent $event) use ($form, $integration): void {
        if ($event->form->id === $form->id) $event->integrations[] = $integration;
    };
    Event::on(Integrations::class, Integrations::EVENT_MODIFY_FORM_INTEGRATIONS, $handler);
    PreflightRetryProvider::$failure = 'transport';
    PreflightRetryProvider::$writes = 0;
    try {
        $runner = Formie::$plugin->getIntegrationRunner();
        $runner->queueSteps($submission, [$integration->handle], \verbb\formie\enums\SubmissionOperation::SUBMIT, ['triggerEvent' => 'submit'], executionKey: 'old-unknown-job');
        $attempts = Formie::$plugin->getDeliveryAttempts();
        $uid = (new Query())->select('uid')->from($attempts::TABLE)->where(['submissionId' => $submission->id, 'step' => 'dispatch'])->scalar();
        expect($runner->runQueuedAttempt($uid)->status->value)->toBe('failed');
        $operations = array_column(array_filter($attempts->supportBundle($uid)['operations'], fn($operation) => $operation['status'] === 'failed'), 'uid');
        expect($operations)->toHaveCount(2);
        $legacy = [...$operations, $uid];
        \craft\helpers\Db::update($attempts::TABLE, ['status' => 'unknown', 'result' => json_encode(['status' => 'unknown', 'code' => 'outcome_unknown'])], ['uid' => $legacy]);
        PreflightRetryProvider::$failure = '';
        expect($runner->runQueuedAttempt($uid)->status->value)->toBe('succeeded')->and(PreflightRetryProvider::$writes)->toBe(2);
        foreach ($legacy as $operation) {
            expect($attempts->get($operation)['status'])->toBe('succeeded');
            expect(array_column($attempts->supportBundle($operation)['checkpoints'], 'checkpoint'))->toContain('result', 'retry');
        }
    } finally {
        Event::off(Integrations::class, Integrations::EVENT_MODIFY_FORM_INTEGRATIONS, $handler);
    }
});
