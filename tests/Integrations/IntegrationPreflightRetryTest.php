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
    public function supportsDeliveryWriteTracking(): bool { return true; }

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

it('does not assume that third-party native-style providers track their writes', function (): void {
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
    expect($runner->runIntegration($integration, $submission, 'custom-sdk', 'queued')->status->value)->toBe('unknown');
    expect($runner->runIntegration($integration, $submission, 'custom-sdk', 'queued')->status->value)->toBe('unknown');
    expect($integration::$writes)->toBe(1);
    expect((new \verbb\formie\integrations\helpdesk\Freshdesk())->supportsDeliveryWriteTracking())->toBeTrue();
    expect((new \verbb\formie\integrations\elements\Entry())->supportsDeliveryWriteTracking())->toBeFalse();
});

it('allows queued retries and manual reruns after a failure before sending', function (string $failure, string $retry): void {
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
        expect(json_decode($child['result'], true)['code'])->toBe('before_send');
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
})->with(['mapping', 'false', 'handled'])->with(['queue', 'manual']);

it('does not replay an uncertain or completed write after a later error', function (string $failure): void {
    $form = formie()->form()->singleLineTextField('fullName')->create();
    $submission = formie()->submission($form)->save();
    $integration = new PreflightRetryProvider(['name' => 'Preflight retry', 'handle' => 'preflightRetry']);
    PreflightRetryProvider::$failure = $failure;
    PreflightRetryProvider::$writes = 0;
    $runner = Formie::$plugin->getIntegrationRunner();
    expect($runner->runIntegration($integration, $submission, 'uncertain', 'queued')->status->value)->toBe('unknown');
    PreflightRetryProvider::$failure = '';
    expect($runner->runIntegration($integration, $submission, 'uncertain', 'queued')->status->value)->toBe('unknown');
    expect(Formie::$plugin->getIntegrationTriggers()->runManualIntegration($integration, $submission)->status->value)->toBe('unknown');
    expect(PreflightRetryProvider::$writes)->toBe(1);
    $attempts = Formie::$plugin->getDeliveryAttempts();
    $latest = (new Query())->from($attempts::TABLE)->where(['submissionId' => $submission->id, 'step' => 'integration'])->orderBy(['id' => SORT_DESC])->one();
    $blockers = $attempts->supportBundle($latest['uid'])['blockingAttempts'];
    expect($blockers)->toHaveCount(1)->and($blockers[0]['uid'])->not->toBe($latest['uid']);
})->with(['transport', 'after-write']);

it('recovers an existing unknown queue delivery by resolving its operations without deleting history', function (): void {
    $form = formie()->form()->singleLineTextField('fullName')->create();
    $submission = formie()->submission($form)->save();
    $integration = new PreflightRetryProvider(['name' => 'Recovery', 'handle' => 'preflightRecovery', 'enabled' => true]);
    $handler = function (ModifyFormIntegrationsEvent $event) use ($form, $integration): void {
        if ($event->form->id === $form->id) $event->integrations[] = $integration;
    };
    Event::on(Integrations::class, Integrations::EVENT_MODIFY_FORM_INTEGRATIONS, $handler);
    $identity = Craft::$app->getUser()->getIdentity();
    PreflightRetryProvider::$failure = 'transport';
    PreflightRetryProvider::$writes = 0;
    try {
        Craft::$app->getUser()->setIdentity(\craft\elements\User::find()->admin(true)->one());
        $runner = Formie::$plugin->getIntegrationRunner();
        $runner->queueSteps($submission, [$integration->handle], \verbb\formie\enums\SubmissionOperation::SUBMIT, ['triggerEvent' => 'submit'], executionKey: 'old-unknown-job');
        $attempts = Formie::$plugin->getDeliveryAttempts();
        $uid = (new Query())->select('uid')->from($attempts::TABLE)->where(['submissionId' => $submission->id, 'step' => 'dispatch'])->scalar();
        expect($runner->runQueuedAttempt($uid)->status->value)->toBe('unknown');
        $bundle = $attempts->supportBundle($uid);
        $integrationUid = $bundle['unresolvedChildren'][0];
        $writeUid = $attempts->supportBundle($integrationUid)['unresolvedChildren'][0];
        $confirmed = IntegrationResult::failed('confirmed_not_delivered', true);
        expect(fn() => $attempts->reconcile($uid, $confirmed, 'Verified absent at provider'))->toThrow(RuntimeException::class, 'Reconcile uncertain child');
        expect(fn() => $attempts->reconcile($integrationUid, $confirmed, 'Verified absent at provider'))->toThrow(RuntimeException::class, 'Reconcile uncertain child');
        foreach ([$writeUid, $integrationUid, $uid] as $operation) {
            $attempts->reconcile($operation, $confirmed, 'Verified absent at provider');
            expect($attempts->hasReconciliation($operation))->toBeTrue();
        }
        PreflightRetryProvider::$failure = '';
        expect($runner->runQueuedAttempt($uid)->status->value)->toBe('succeeded')->and(PreflightRetryProvider::$writes)->toBe(2);
        foreach ([$writeUid, $integrationUid, $uid] as $operation) {
            expect($attempts->get($operation)['status'])->toBe('succeeded');
        }
    } finally {
        Craft::$app->getUser()->setIdentity($identity);
        Event::off(Integrations::class, Integrations::EVENT_MODIFY_FORM_INTEGRATIONS, $handler);
    }
});
