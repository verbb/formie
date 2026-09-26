<?php

use craft\db\Query;
use craft\helpers\Json;
use verbb\formie\Formie;
use verbb\formie\enums\IntegrationStatus;
use verbb\formie\errors\IntegrationStepException;
use verbb\formie\helpers\IntegrationSecrets;
use verbb\formie\helpers\Table;
use verbb\formie\models\IntegrationExecutionContext;
use verbb\formie\models\IntegrationResult;
use verbb\formie\models\IntegrationDispatchPlan;
use verbb\formie\integrations\helpdesk\Freshdesk;

class DurableFreshdeskFixture extends Freshdesk
{
    public static array $requests = [];
    public static $handler;
    protected function createDeliveryHttpHandler(): callable { return self::$handler; }
    public function validate($attributeNames = null, $clearErrors = true): bool { return true; }
    public function getFieldMappingValues($submission, $mapping, $settings = []) { return ['name' => 'Synthetic person', 'email' => 'fixture@example.test']; }
    public function getFieldMappingMultipartValues($submission, $mapping, $settings = []) { return ['subject' => 'Test', 'description' => 'Synthetic issue']; }
}

it('retries the definitely failed second write of the actual Freshdesk provider without recreating the contact', function () {
    $form = formie()->form()->singleLineTextField('name')->create();
    $submission = formie()->submission($form)->save();
    $fixture = new DurableFreshdeskFixture(['name' => 'Durable Freshdesk', 'handle' => 'durableFreshdesk', 'apiDomain' => 'https://8.8.8.8', 'apiKey' => 'synthetic-api-secret', 'mapToContact' => true, 'mapToTicket' => true, 'contactFieldMapping' => [], 'ticketFieldMapping' => []]);
    DurableFreshdeskFixture::$requests = [];
    $responses = new \GuzzleHttp\Handler\MockHandler([
        new \GuzzleHttp\Psr7\Response(201, [], '{"id":123}'),
        new IntegrationStepException(IntegrationResult::failed('confirmed_not_sent', true)),
        new \GuzzleHttp\Psr7\Response(201, [], '{"id":456}'),
    ]);
    $stack = \GuzzleHttp\HandlerStack::create($responses);
    $stack->push(\GuzzleHttp\Middleware::history(DurableFreshdeskFixture::$requests));
    DurableFreshdeskFixture::$handler = $stack;
    $runner = Formie::$plugin->getIntegrationRunner();
    $firstResult = $runner->runIntegration($fixture, $submission, 'multi-write', 'queued');
    expect($firstResult->status)->toBe(IntegrationStatus::Failed);
    expect($runner->runIntegration($fixture, $submission, 'multi-write', 'queued')->status)->toBe(IntegrationStatus::Succeeded);
    expect(array_map(fn($r) => $r['request']->getUri()->getPath(), DurableFreshdeskFixture::$requests))->toBe(['/api/v2/contacts', '/api/v2/tickets', '/api/v2/tickets']);
    $attempts = Formie::$plugin->getDeliveryAttempts();
    $root = (new Query())->from($attempts::TABLE)->where(['submissionId' => $submission->id, 'step' => 'integration'])->one();
    $bundle = Json::encode($attempts->supportBundle($root['uid']));
    expect($bundle)->toContain('mapping-inputs', 'submission-projection', 'retry')->not->toContain('synthetic-api-secret');
});

it('encrypts literal connection and binding secrets while preserving environment references and hydration', function () {
    $settings = ['apiKey' => 'literal-api-secret', 'clientSecret' => '$CRM_SECRET', 'httpAuth' => ['username' => 'literal-user', 'password' => 'literal-password'], 'fieldMapping' => ['name' => 'field:name']];
    $sealed = IntegrationSecrets::protect($settings);
    expect(Json::encode($sealed))->not->toContain('literal-api-secret', 'literal-password', 'literal-user')->toContain('$CRM_SECRET');
    expect(IntegrationSecrets::protect($sealed))->toBe($sealed)->and(IntegrationSecrets::reveal($sealed))->toBe($settings);
    $integration = new Freshdesk(['name' => 'Encrypted connection', 'handle' => 'encrypted' . uniqid(), 'apiDomain' => 'https://example.test', 'apiKey' => 'at-rest-secret']);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();
    $raw = (new Query())->from(Table::FORMIE_INTEGRATIONS)->where(['id' => $integration->id])->one();
    expect($raw['settings'])->not->toContain('at-rest-secret');
    unset($raw['dateDeleted']);
    expect(Formie::$plugin->getIntegrations()->createIntegration($raw)->apiKey)->toBe('at-rest-secret');
});

it('migrates persisted plans and secrets idempotently without dispatching', function () {
    $form = formie()->form()->singleLineTextField('name')->create();
    $settings = $form->settings->toArray();
    $settings['integrationDispatch'] = ['enabled' => true, 'notificationTiming' => 'afterIntegrations', 'steps' => [['handle' => 'webRequest', 'mode' => 'immediate']]];
    $settings['integrations'] = ['webRequest' => ['enabled' => true, 'httpAuth' => ['password' => 'legacy-plain-password']]];
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_FORMS, ['settings' => Json::encode($settings)], ['id' => $form->id])->execute();
    $migration = new \verbb\formie\migrations\m260927_000000_delivery_attempts();
    ob_start();
    try { expect($migration->safeUp())->toBeTrue(); expect($migration->safeUp())->toBeTrue(); } finally { ob_end_clean(); }
    $stored = (new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar();
    expect($stored)->not->toContain('legacy-plain-password', 'immediate', 'afterIntegrations');
    $hydrated = new \verbb\formie\models\FormSettings(Json::decode($stored));
    expect($hydrated->integrations['webRequest']['httpAuth']['password'])->toBe('legacy-plain-password');
    expect($hydrated->integrationDispatch['steps'][0]['execution'])->toBe('synchronous');
});

it('defines notification terminal policy for every normalized result', function (string $status, bool $successful, bool $finalized) {
    $success = IntegrationDispatchPlan::fromFormSettings(['completionPolicy' => 'successful']);
    $all = IntegrationDispatchPlan::fromFormSettings(['completionPolicy' => 'finalized']);
    expect(in_array($status, $success->acceptedStatuses(), true))->toBe($successful);
    expect(in_array($status, $all->acceptedStatuses(), true))->toBe($finalized);
})->with([['succeeded', true, true], ['skipped', true, true], ['failed', false, true], ['rejected', false, true], ['unknown', false, false], ['pending', false, false]]);

it('preserves legacy queue locators without restoring their debug payloads', function () {
    $job = new \verbb\formie\jobs\TriggerIntegration();
    $job->__unserialize(['submissionId' => 123, 'integrationHandle' => 'crm', 'executionUid' => 'saved-identity', 'payload' => ['password' => 'must-not-return'], 'integrationData' => ['apiKey' => 'must-not-return']]);
    $serialized = serialize($job);
    expect($serialized)->toContain('saved-identity')->not->toContain('must-not-return', 'integrationData');
    expect(serialize(unserialize($serialized)))->toBe($serialized);
});

it('denies reconciliation and sensitive export without authority', function () {
    $form = formie()->form()->singleLineTextField('name')->create();
    $submission = formie()->submission($form)->save();
    $attempts = Formie::$plugin->getDeliveryAttempts();
    $uid = $attempts->prepare(new IntegrationExecutionContext($submission->id, $form->id, 'permissions', 'permissions'), 'integration');
    $attempts->execute($uid, fn() => IntegrationResult::unknown());
    $identity = Craft::$app->getUser()->getIdentity();
    Craft::$app->getUser()->setIdentity(null);
    try {
        expect(fn() => $attempts->reconcile($uid, IntegrationResult::succeeded(), 'Confirmed externally'))->toThrow(RuntimeException::class);
        expect(fn() => $attempts->sensitiveEvidence($uid))->toThrow(RuntimeException::class);
    } finally { Craft::$app->getUser()->setIdentity($identity); }
    expect($attempts->get($uid)['status'])->toBe('unknown');
});

class DeliveryLaneProvider extends \verbb\formie\base\Integration
{
    public static array $order = [];
    public static function displayName(): string { return 'Delivery lane fixture'; }
    public function fetchFormSettings(): \verbb\formie\models\IntegrationFormSettings { return new \verbb\formie\models\IntegrationFormSettings(); }
    public function sendPayload(\verbb\formie\elements\Submission $submission): IntegrationResult { self::$order[] = $this->handle; return IntegrationResult::succeeded(); }
}

it('runs the complete synchronous lane before enqueueing the queued lane and honors all three notification timings', function () {
    $form = formie()->form()->singleLineTextField('name')->create();
    $form->settings->integrationDispatch = ['enabled' => true, 'steps' => [
        ['handle' => 'q1', 'execution' => 'queued'], ['handle' => 's1', 'execution' => 'synchronous'],
        ['handle' => 'q2', 'execution' => 'queued'], ['handle' => 's2', 'execution' => 'synchronous'],
    ]];
    $notifications = [];
    foreach (['beforeIntegrations', 'afterSynchronousIntegrations', 'afterFinalizedDeliveryAttempts'] as $timing) {
        $notifications[] = new \verbb\formie\models\Notification(['formId' => $form->id, 'enabled' => true, 'name' => $timing, 'handle' => $timing, 'subject' => 'Timing test', 'to' => 'timing@example.test', 'dispatchTiming' => $timing]);
    }
    foreach ($notifications as $notification) {
        expect(Formie::$plugin->getNotifications()->saveNotification($notification, false))->toBeTrue();
    }
    $form->setNotifications($notifications);
    $submission = formie()->submission($form)->save();
    $submission->setForm($form);
    $listener = function ($event) use ($form) {
        if ($event->form->id === $form->id) {
            foreach (['q1', 's1', 'q2', 's2'] as $handle) $event->integrations[] = new DeliveryLaneProvider(['name' => $handle, 'handle' => $handle, 'enabled' => true]);
        }
    };
    $oldNotifications = Formie::$plugin->getNotifications();
    $recorder = new class extends \verbb\formie\services\Notifications {
        public function sendNotification(\verbb\formie\models\Notification $notification, \verbb\formie\elements\Submission $submission, ?bool $useQueue = null, ?string $deliveryKey = null): void { DeliveryLaneProvider::$order[] = $notification->name; }
    };
    $oldQueue = Formie::$plugin->getSettings()->useQueueForIntegrations;
    Formie::$plugin->getSettings()->useQueueForIntegrations = true;
    Formie::$plugin->set('notifications', $recorder);
    \yii\base\Event::on(\verbb\formie\services\Integrations::class, \verbb\formie\services\Integrations::EVENT_MODIFY_FORM_INTEGRATIONS, $listener);
    DeliveryLaneProvider::$order = [];
    try {
        $dispatcher = Formie::$plugin->getIntegrationDispatcher();
        $dispatcher->sendNotifications($submission, $dispatcher::PHASE_BEFORE);
        $dispatcher->dispatchSubmission($submission);
        expect(DeliveryLaneProvider::$order)->toBe(['beforeIntegrations', 's1', 's2', 'afterSynchronousIntegrations']);
        $attempts = Formie::$plugin->getDeliveryAttempts();
        $queued = (new Query())->from($attempts::TABLE)->where(['submissionId' => $submission->id, 'step' => 'dispatch'])->one();
        expect($queued['status'])->toBe('pending');
        // The worker reloads the persisted form, as it would after a new request.
        Craft::$app->getElements()->saveElement($form, false);
        expect(Formie::$plugin->getIntegrationRunner()->runQueuedAttempt($queued['uid'])->status)->toBe(IntegrationStatus::Succeeded);
        expect(DeliveryLaneProvider::$order)->toBe(['beforeIntegrations', 's1', 's2', 'afterSynchronousIntegrations', 'q1', 'q2', 'afterFinalizedDeliveryAttempts']);
    } finally {
        Formie::$plugin->getSettings()->useQueueForIntegrations = $oldQueue;
        Formie::$plugin->set('notifications', $oldNotifications);
        \yii\base\Event::off(\verbb\formie\services\Integrations::class, \verbb\formie\services\Integrations::EVENT_MODIFY_FORM_INTEGRATIONS, $listener);
    }
});

it('enforces notification completion policy against stored results of the same execution', function (string $status, string $policy, bool $expected) {
    $form = formie()->form()->singleLineTextField('name')->create();
    $form->settings->integrationDispatch = ['enabled' => true, 'completionPolicy' => $policy, 'notificationTiming' => 'afterFinalizedDeliveryAttempts', 'steps' => [['handle' => 'remote', 'execution' => 'queued']]];
    $form->setNotifications([new \verbb\formie\models\Notification(['enabled' => true])]);
    $submission = formie()->submission($form)->save();
    $submission->setForm($form);
    $submission->integrationDispatchContext = ['remote' => ['status' => $status, 'executionUid' => 'policy-run']];
    $original = Formie::$plugin->getNotifications();
    $recorder = new class extends \verbb\formie\services\Notifications {
        public int $sent = 0;
        public function sendNotification(\verbb\formie\models\Notification $notification, \verbb\formie\elements\Submission $submission, ?bool $useQueue = null, ?string $deliveryKey = null): void { $this->sent++; }
    };
    Formie::$plugin->set('notifications', $recorder);
    try {
        $dispatcher = Formie::$plugin->getIntegrationDispatcher();
        $context = new \verbb\formie\models\IntegrationDispatchContext();
        $context->record('remote', ['status' => $status, 'executionUid' => 'policy-run']);
        $dispatcher->saveContext($submission, $context);
        $dispatcher->sendNotifications($submission, $dispatcher::PHASE_AFTER, 'different-run');
        expect($recorder->sent)->toBe(0);
        $dispatcher->sendNotifications($submission, $dispatcher::PHASE_AFTER, 'policy-run');
        expect($recorder->sent)->toBe($expected ? 1 : 0);
    } finally { Formie::$plugin->set('notifications', $original); }
})->with([
    ['succeeded', 'successful', true], ['skipped', 'successful', true], ['failed', 'successful', false], ['rejected', 'successful', false], ['unknown', 'successful', false],
    ['succeeded', 'finalized', true], ['skipped', 'finalized', true], ['failed', 'finalized', true], ['rejected', 'finalized', true], ['unknown', 'finalized', false],
]);

it('requires scoped force authority and a reason and records the ordinary ineligible decision', function () {
    $form = formie()->form()->singleLineTextField('name')->create();
    $submission = formie()->submission($form)->save();
    $provider = new class(['name' => 'Force fixture', 'handle' => 'forceFixture']) extends \verbb\formie\base\Integration {
        public static int $calls = 0;
        public static function displayName(): string { return 'Force fixture'; }
        public function fetchFormSettings(): \verbb\formie\models\IntegrationFormSettings { return new \verbb\formie\models\IntegrationFormSettings(); }
        public function shouldTrigger(\verbb\formie\elements\Submission $submission, array $triggerContext = []): bool { return false; }
        public function sendPayload(\verbb\formie\elements\Submission $submission): IntegrationResult { self::$calls++; return IntegrationResult::succeeded(); }
    };
    $runner = Formie::$plugin->getIntegrationRunner();
    $identity = Craft::$app->getUser()->getIdentity();
    try {
        expect($runner->runIntegration($provider, $submission, 'manual', 'synchronous', ['operatorInitiated' => true])->status)->toBe(IntegrationStatus::Skipped);
        Craft::$app->getUser()->setIdentity(null);
        expect(fn() => $runner->runIntegration($provider, $submission, 'denied', 'synchronous', ['force' => true, 'reason' => 'Check']))->toThrow(RuntimeException::class);
        Craft::$app->getUser()->setIdentity(\craft\elements\User::find()->admin(true)->one());
        expect(fn() => $runner->runIntegration($provider, $submission, 'no-reason', 'synchronous', ['force' => true]))->toThrow(RuntimeException::class);
        expect($runner->runIntegration($provider, $submission, 'force', 'synchronous', ['force' => true, 'reason' => 'Authorized synthetic test'])->status)->toBe(IntegrationStatus::Succeeded);
        expect($provider::$calls)->toBe(1);
        $attempts = Formie::$plugin->getDeliveryAttempts();
        $uid = (new Query())->select('uid')->from($attempts::TABLE)->where(['submissionId' => $submission->id, 'executionUid' => 'force'])->scalar();
        $context = $attempts->context($uid);
        expect($context->eligible)->toBeFalse()->and($context->overrides)->toBe(['conditions', 'optIn'])->and($context->reason)->toBe('Authorized synthetic test');
    } finally { Craft::$app->getUser()->setIdentity($identity); }
});

it('projects a reconciled integration outcome before finalizing delivery notifications', function () {
    $form = formie()->form()->singleLineTextField('name')->create();
    $form->settings->integrationDispatch = ['enabled' => true, 'steps' => [['handle' => 'reconciledProvider', 'execution' => 'queued']], 'notificationTiming' => 'afterFinalizedDeliveryAttempts'];
    expect(Craft::$app->getElements()->saveElement($form, false))->toBeTrue();
    $submission = formie()->submission($form)->save();
    $attempts = Formie::$plugin->getDeliveryAttempts();
    $uid = $attempts->prepare(new IntegrationExecutionContext($submission->id, $form->id, 'reconciledProvider', 'reconciled-run', 'queued'), 'integration');
    $attempts->execute($uid, fn() => IntegrationResult::unknown());
    $identity = Craft::$app->getUser()->getIdentity();
    try {
        Craft::$app->getUser()->setIdentity(\craft\elements\User::find()->admin(true)->one());
        $attempts->reconcile($uid, IntegrationResult::succeeded('verified-provider-id'), 'Provider confirmed delivery.');
        $fresh = \verbb\formie\elements\Submission::find()->id($submission->id)->status(null)->one();
        $projection = Formie::$plugin->getIntegrationDispatcher()->loadContext($fresh)->getResult('reconciledProvider');
        expect($projection['status'])->toBe('succeeded')->and($projection['executionUid'])->toBe('reconciled-run');
        expect((new Query())->from($attempts::TABLE)->where(['submissionId' => $submission->id, 'step' => 'finalized', 'status' => 'succeeded'])->exists())->toBeTrue();
    } finally { Craft::$app->getUser()->setIdentity($identity); }
});
