<?php

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\enums\IntegrationStatus;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\models\IntegrationFormSettings;
use verbb\formie\models\IntegrationResult;
use verbb\formie\models\Notification;
use verbb\formie\services\Emails;
use verbb\formie\services\IntegrationDispatcher;
use verbb\formie\services\Integrations;
use yii\base\Event;

class NotificationTimingProvider extends Integration
{
    public IntegrationStatus $fixtureStatus = IntegrationStatus::Succeeded;
    public static array $calls = [];
    public static function displayName(): string { return 'Timing fixture'; }
    public function fetchFormSettings(): IntegrationFormSettings { return new IntegrationFormSettings(); }
    public function shouldTrigger(Submission $submission, array $triggerContext = []): bool { return true; }
    public function sendPayload(Submission $submission): IntegrationResult {
        self::$calls[] = $this->handle;
        return new IntegrationResult($this->fixtureStatus);
    }
}

it('separates timing from success across lanes failure policies and explicit conditions', function (string $execution, string $failurePolicy, string $status) {
    $form = formie()->form()->singleLineTextField('message')->create();
    $form->settings->integrationDispatch = ['enabled' => true, 'failurePolicy' => $failurePolicy, 'steps' => [
        ['handle' => 'remote', 'execution' => $execution], ['handle' => 'next', 'execution' => $execution],
    ]];
    $notifications = [];
    foreach ([Notification::DISPATCH_TIMING_BEFORE, Notification::DISPATCH_TIMING_SYNCHRONOUS, Notification::DISPATCH_TIMING_AFTER] as $timing) {
        $notifications[] = new Notification(['name' => $timing, 'handle' => 'n' . count($notifications), 'enabled' => true, 'dispatchTiming' => $timing]);
    }
    $notifications[] = new Notification(['name' => 'explicit-success', 'handle' => 'requiresSuccess', 'enabled' => true,
        'dispatchTiming' => Notification::DISPATCH_TIMING_AFTER, 'enableConditions' => true,
        'conditions' => ['sendRule' => 'send', 'conditionRule' => 'all', 'conditions' => [
            ['field' => '{dispatch:remote.success}', 'condition' => '=', 'value' => true],
        ]],
    ]);
    expect(Craft::$app->getElements()->saveElement($form, false))->toBeTrue();
    foreach ($notifications as $notification) {
        $notification->formId = $form->id;
        expect(Formie::$plugin->getNotifications()->saveNotification($notification, false))->toBeTrue();
    }
    $form->setNotifications(array_map(fn($notification) => Formie::$plugin->getNotifications()->getNotificationById($notification->id), $notifications));
    $submission = formie()->submission($form)->save();
    $submission->setForm($form);
    $oldEmails = Formie::$plugin->getEmails();
    $emails = new class extends Emails {
        public array $sent = [];
        public function sendEmail(Notification $notification, Submission $submission, mixed $queueJob = null, bool $createSentNotification = true): array {
            $this->sent[] = $notification->name;
            return ['success' => true];
        }
    };
    Formie::$plugin->set('emails', $emails);
    Formie::$plugin->getSettings()->useQueueForNotifications = false;
    $listener = static function ($event) use ($form, $status) {
        if ($event->form->id === $form->id) {
            $event->integrations[] = new NotificationTimingProvider(['name' => 'Remote', 'handle' => 'remote', 'enabled' => true, 'fixtureStatus' => IntegrationStatus::from($status)]);
            $event->integrations[] = new NotificationTimingProvider(['name' => 'Next', 'handle' => 'next', 'enabled' => true]);
        }
    };
    Event::on(Integrations::class, Integrations::EVENT_MODIFY_FORM_INTEGRATIONS, $listener);
    NotificationTimingProvider::$calls = [];
    try {
        $dispatcher = Formie::$plugin->getIntegrationDispatcher();
        $runner = Formie::$plugin->getIntegrationRunner();
        $plan = $dispatcher->getPlan($form);
        $key = 'timing-fixture';
        $dispatcher->sendNotifications($submission, IntegrationDispatcher::PHASE_BEFORE, $key);
        if ($execution === 'queued') {
            $runner->queueSteps($submission, ['remote', 'next'], SubmissionOperation::SUBMIT, [], true, $key);
            $dispatcher->sendNotifications($submission, IntegrationDispatcher::PHASE_SYNCHRONOUS, $key);
            $attempt = (new \craft\db\Query())->from(\verbb\formie\services\DeliveryAttempts::TABLE)->where(['submissionId' => $submission->id, 'step' => 'dispatch'])->one();
            $runner->runQueuedAttempt($attempt['uid']);
        } else {
            $runner->runSteps($submission, ['remote', 'next'], [], $plan, $key);
            $dispatcher->sendNotifications($submission, IntegrationDispatcher::PHASE_SYNCHRONOUS, $key);
            $dispatcher->sendNotifications($submission, IntegrationDispatcher::PHASE_AFTER, $key);
        }
        $expected = [Notification::DISPATCH_TIMING_BEFORE];
        if ($execution === 'queued' || $status !== 'unknown') {
            $expected[] = Notification::DISPATCH_TIMING_SYNCHRONOUS;
        }
        if ($status !== 'unknown') {
            $expected[] = Notification::DISPATCH_TIMING_AFTER;
        }
        if ($status === 'succeeded') {
            $expected[] = 'explicit-success';
        }
        expect($emails->sent)->toBe($expected);
        $stops = $failurePolicy === 'stop' && in_array($status, ['failed', 'rejected', 'unknown'], true);
        expect(NotificationTimingProvider::$calls)->toBe($stops ? ['remote'] : ['remote', 'next']);
        $context = $dispatcher->loadContext($submission, $key);
        expect($context->getResult('next')['status'])->toBe($stops ? 'skipped' : 'succeeded');
    } finally {
        Formie::$plugin->set('emails', $oldEmails);
        Event::off(Integrations::class, Integrations::EVENT_MODIFY_FORM_INTEGRATIONS, $listener);
    }
})->with(['synchronous', 'queued'])->with(['continue', 'stop'])->with(['succeeded', 'skipped', 'failed', 'rejected', 'unknown']);
