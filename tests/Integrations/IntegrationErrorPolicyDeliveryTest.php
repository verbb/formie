<?php

use craft\db\Query;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\helpers\References;
use verbb\formie\integrations\emailmarketing\CampaignMonitor;
use verbb\formie\integrations\emailmarketing\Mailchimp;
use verbb\formie\jobs\TriggerIntegration;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationResult;
use verbb\formie\models\Notification;
use verbb\formie\services\Emails;
use verbb\formie\services\Integrations;
use yii\base\Event;
use yii\log\Logger;

trait ErrorPolicyHttpFixture
{
    public static HandlerStack $handler;
    public static array $requests = [];
    protected function createDeliveryHttpHandler(): callable { return self::$handler; }
    protected function defineClient(): Client { return new Client(['base_uri' => 'https://8.8.8.8/']); }
}
class ErrorPolicyMailchimp extends Mailchimp { use ErrorPolicyHttpFixture; }
class ErrorPolicyCampaignMonitor extends CampaignMonitor { use ErrorPolicyHttpFixture; }
class ErrorPolicyNext extends Integration
{
    public static int $calls = 0;
    public static bool $fail = false;
    public static function displayName(): string { return 'Next'; }
    public function fetchConfig(): IntegrationConfig { return new IntegrationConfig(); }
    public function sendPayload(Submission $submission): IntegrationResult {
        self::$calls++;
        return self::$fail ? IntegrationResult::failed() : IntegrationResult::succeeded();
    }
}

function withErrorPolicyDelivery(string $provider, array $responses, callable $callback, array $handling = [], bool $synchronous = false, array $connectionHandling = []): void
{
    $settings = Formie::$plugin->getSettings();
    $oldHandling = $settings->integrationApiErrorHandling;
    $oldQueue = $settings->useQueueForNotifications;
    $settings->integrationApiErrorHandling = $handling;
    $settings->useQueueForNotifications = false;
    $logger = Craft::getLogger();
    $oldFlush = $logger->flushInterval;
    $logger->flushInterval = 0;
    $provider::$requests = [];
    $provider::$handler = HandlerStack::create(new MockHandler($responses));
    $provider::$handler->push(Middleware::history($provider::$requests));
    ErrorPolicyNext::$calls = 0;
    ErrorPolicyNext::$fail = false;
    $form = formie()->form()->singleLineTextField('email')->create();
    $form->settings->integrationDispatch = ['enabled' => true, 'failurePolicy' => 'stop', 'steps' => [
        ['handle' => 'subscriber', 'execution' => 'queued'], ['handle' => 'next', 'execution' => 'queued'],
    ]];
    Craft::$app->getElements()->saveElement($form, false);
    $submission = formie()->submission($form)->with(['email' => 'subscriber@example.test'])->save();
    $field = $provider === ErrorPolicyMailchimp::class ? 'email_address' : 'Email';
    $integration = new $provider([
        'name' => 'Newsletter', 'handle' => 'subscriber', 'enabled' => true,
        'apiKey' => 'synthetic-policy-secret', 'listId' => 'list', 'apiErrorHandling' => $connectionHandling,
        'fieldMapping' => [$field => References::field($form->getFieldByHandle('email')->reference)],
    ]);
    $config = new IntegrationConfig(['lists' => [new IntegrationCollection([
        'id' => 'list', 'name' => 'Newsletter',
        'fields' => [new IntegrationField(['handle' => $field, 'name' => 'Email'])],
    ])]], $integration->getIntegrationConfigKey(), time());
    $integration->cache = ['config' => $config->toStorage()];
    $listener = function($event) use ($form, $integration): void {
        if ($event->form->id === $form->id) {
            $event->integrations[] = $integration;
            $event->integrations[] = new ErrorPolicyNext(['name' => 'Next', 'handle' => 'next', 'enabled' => true]);
        }
    };
    $failures = [];
    $onFailure = function($event) use (&$failures): void { $failures[] = $event->integration->handle; };
    Event::on(Integrations::class, Integrations::EVENT_MODIFY_FORM_INTEGRATIONS, $listener);
    Event::on(Integrations::class, Integrations::EVENT_AFTER_TRIGGER_INTEGRATION_FAILED, $onFailure);
    try {
        $runner = Formie::$plugin->getIntegrationRunner();
        $key = 'error-policy-' . $submission->id;
        if ($synchronous) {
            $callback(fn() => $runner->runSteps($submission, ['subscriber', 'next'], [], Formie::$plugin->getIntegrationDispatcher()->getPlan($form), $key), $submission, $key, $form, $failures, null, $integration);
        } else {
            $runner->queueSteps($submission, ['subscriber', 'next'], SubmissionOperation::SUBMIT, ['triggerEvent' => 'submit'], true, $key);
            $uid = (new Query())->select('uid')->from(\verbb\formie\services\DeliveryAttempts::TABLE)->where(['submissionId' => $submission->id, 'step' => 'dispatch'])->scalar();
            $job = new TriggerIntegration(['deliveryAttemptUid' => $uid]);
            $callback(fn() => $job->execute(Craft::$app->getQueue()), $submission, $key, $form, $failures, $uid, $integration);
        }
    } finally {
        Event::off(Integrations::class, Integrations::EVENT_MODIFY_FORM_INTEGRATIONS, $listener);
        Event::off(Integrations::class, Integrations::EVENT_AFTER_TRIGGER_INTEGRATION_FAILED, $onFailure);
        $settings->integrationApiErrorHandling = $oldHandling;
        $settings->useQueueForNotifications = $oldQueue;
        $logger->flushInterval = $oldFlush;
    }
}

function errorPolicyRejection(string $provider): Response
{
    return $provider === ErrorPolicyMailchimp::class
        ? new Response(400, [], '{"api_key":"synthetic-policy-secret","title":"Invalid Resource","detail":"subscriber@example.test looks fake or invalid, please enter a real email address."}')
        : new Response(429, [], '{"Code":429,"Message":"Subscriber was added too many times too quickly, try again later."}');
}

it('skips known subscriber rejections through the queue or synchronous batch and logs a standalone explanation', function(string $provider, bool $sync): void {
    withErrorPolicyDelivery($provider, [errorPolicyRejection($provider)], function($run, $submission, $key, $form, &$failures, $uid = null) use ($provider): void {
        $start = count(Craft::getLogger()->messages);
        $run();
        $result = Formie::$plugin->getIntegrationDispatcher()->loadRunResults($submission, $key)->getResult('subscriber');
        expect($result['status'])->toBe('skipped')->and($result['success'])->toBeFalse()
            ->and($result['code'])->toBe('api_error_rejected')->and(ErrorPolicyNext::$calls)->toBe(1)
            ->and($failures)->toBe([])->and($provider::$requests)->toHaveCount(1);
        if ($uid) {
            expect(Formie::$plugin->getDeliveryAttempts()->get($uid)['status'])->toBe('succeeded');
        }
        $logs = array_values(array_filter(array_slice(Craft::getLogger()->messages, $start), fn($entry) => is_string($entry[0]) && str_contains($entry[0], 'Delivery skipped by Integration API Error Handling')));
        expect($logs)->toHaveCount(1);
        expect($logs[0][1])->toBe(Logger::LEVEL_WARNING)
            ->and($logs[0][0])->toContain('Newsletter', $provider::displayName(), $form->handle, 'submission #' . $submission->id, 'Known subscriber rejections', 'Skip and log warning', 'HTTP ')
            ->and($logs[0][0])->not->toContain('synthetic-policy-secret');
    }, synchronous: $sync);
})->with([ErrorPolicyMailchimp::class, ErrorPolicyCampaignMonitor::class])->with([false, true]);

it('honours connection failure info warning and silent actions over legacy global choices', function(string $provider, string $action): void {
    withErrorPolicyDelivery($provider, [errorPolicyRejection($provider)], function($run, $submission, $key, $form, &$failures) use ($action, $provider): void {
        $start = count(Craft::getLogger()->messages);
        if ($action === 'failQueue') {
            expect($run)->toThrow(RuntimeException::class);
        } else {
            $run();
        }
        $result = Formie::$plugin->getIntegrationDispatcher()->loadRunResults($submission, $key)->getResult('subscriber');
        expect($result['status'])->toBe($action === 'failQueue' ? 'failed' : 'skipped')
            ->and(ErrorPolicyNext::$calls)->toBe($action === 'failQueue' ? 0 : 1)
            ->and($failures)->toBe($action === 'failQueue' ? ['subscriber'] : []);
        $logs = array_values(array_filter(array_slice(Craft::getLogger()->messages, $start), fn($entry) => is_string($entry[0]) && str_contains($entry[0], 'by Integration API Error Handling')));
        expect($logs)->toHaveCount($action === 'ignore' ? 0 : 1);
        if ($action !== 'ignore') {
            expect($logs[0][1])->toBe(match ($action) { 'failQueue' => Logger::LEVEL_ERROR, 'logInfo' => Logger::LEVEL_INFO, default => Logger::LEVEL_WARNING });
            expect($logs[0][0])->toContain($form->handle, 'submission #' . $submission->id, $provider === ErrorPolicyMailchimp::class ? 'HTTP 400' : 'HTTP 429');
        }
    }, [['severity' => 'rejected', 'action' => $action === 'failQueue' ? 'ignore' : 'failQueue']], connectionHandling: ['rejected' => $action]);
})->with([ErrorPolicyMailchimp::class, ErrorPolicyCampaignMonitor::class])->with(['failQueue', 'logInfo', 'logWarning', 'ignore']);

it('fails real API errors and retries using current settings and values', function(string $kind): void {
    $response = match ($kind) {
        'rate' => new Response(429, [], '{"title":"Too Many Requests"}'),
        'mapping' => new Response(400, [], '{"title":"Invalid Resource","detail":"Your merge fields were invalid."}'),
        'auth' => new Response(401),
        'outage' => new Response(503),
        default => new ConnectException('Response lost', new Request('PUT', 'https://8.8.8.8/lists/list/members/test')),
    };
    withErrorPolicyDelivery(ErrorPolicyMailchimp::class, [$response, new Response(200, [], '{}')], function($run, $submission, $key, $form, &$failures, $uid): void {
        expect($run)->toThrow(RuntimeException::class);
        expect(Formie::$plugin->getDeliveryAttempts()->get($uid)['status'])->toBe('failed')->and(ErrorPolicyNext::$calls)->toBe(0);
        $submission->setFieldValue('email', 'updated@example.test');
        expect(Craft::$app->getElements()->saveElement($submission, false))->toBeTrue();
        $run();
        expect(Formie::$plugin->getDeliveryAttempts()->get($uid)['status'])->toBe('succeeded')->and(ErrorPolicyNext::$calls)->toBe(1);
        $payload = json_decode((string)ErrorPolicyMailchimp::$requests[1]['request']->getBody(), true);
        expect($payload['email_address'])->toBe('updated@example.test');
    });
})->with(['rate', 'mapping', 'auth', 'outage', 'transport']);

it('keeps the enclosing job failed when a later integration fails after a policy skip', function(): void {
    withErrorPolicyDelivery(ErrorPolicyCampaignMonitor::class, [errorPolicyRejection(ErrorPolicyCampaignMonitor::class)], function($run, $submission, $key, $form, &$failures, $uid): void {
        ErrorPolicyNext::$fail = true;
        expect($run)->toThrow(RuntimeException::class);
        expect(Formie::$plugin->getDeliveryAttempts()->get($uid)['status'])->toBe('failed')->and($failures)->toBe(['next']);
    });
});

it('keeps skipped deliveries out of notifications that explicitly require success', function(): void {
    withErrorPolicyDelivery(ErrorPolicyMailchimp::class, [errorPolicyRejection(ErrorPolicyMailchimp::class)], function($run, $submission, $key, $form): void {
        $notification = new Notification(['name' => 'Requires success', 'handle' => 'requiresSuccess', 'enabled' => true, 'formId' => $form->id,
            'dispatchTiming' => Notification::DISPATCH_TIMING_AFTER, 'enableConditions' => true,
            'conditions' => ['sendRule' => 'send', 'conditionRule' => 'all', 'conditions' => [
                ['field' => '{dispatch:subscriber.success}', 'condition' => '=', 'value' => true],
            ]],
        ]);
        expect(Formie::$plugin->getNotifications()->saveNotification($notification, false))->toBeTrue();
        $ordinary = new Notification(['name' => 'Ordinary after delivery', 'handle' => 'ordinaryAfter', 'enabled' => true, 'formId' => $form->id, 'dispatchTiming' => Notification::DISPATCH_TIMING_AFTER]);
        expect(Formie::$plugin->getNotifications()->saveNotification($ordinary, false))->toBeTrue();
        $form->setNotifications([$notification, $ordinary]);
        $oldEmails = Formie::$plugin->getEmails();
        $emails = new class extends Emails {
            public array $sent = [];
            public function sendEmail(Notification $notification, Submission $submission, mixed $queueJob = null, bool $createSentNotification = true): array { $this->sent[] = $notification->handle; return ['success' => true]; }
        };
        Formie::$plugin->set('emails', $emails);
        try {
            $run();
            expect($emails->sent)->toBe(['ordinaryAfter']);
        } finally {
            Formie::$plugin->set('emails', $oldEmails);
        }
    });
});

it('applies a changed rate-limit policy when retrying a failed job', function(): void {
    withErrorPolicyDelivery(ErrorPolicyMailchimp::class, [new Response(429), new Response(429)], function($run, $submission, $key, $form, &$failures, $uid, $integration): void {
        expect($run)->toThrow(RuntimeException::class);
        $integration->apiErrorHandling = ['rate_limited' => 'logInfo'];
        $run();
        expect(Formie::$plugin->getDeliveryAttempts()->get($uid)['status'])->toBe('succeeded')
            ->and(ErrorPolicyMailchimp::$requests)->toHaveCount(2)->and(ErrorPolicyNext::$calls)->toBe(1);
        $result = Formie::$plugin->getIntegrationDispatcher()->loadRunResults($submission, $key)->getResult('subscriber');
        expect($result['status'])->toBe('skipped')->and($result['code'])->toBe('api_error_rate_limited');
    });
});

it('keeps policy logs useful while respecting disabled submission-value logging', function(): void {
    $settings = Formie::$plugin->getSettings();
    $previous = $settings->logSubmissionValues;
    $settings->logSubmissionValues = false;
    try {
        withErrorPolicyDelivery(ErrorPolicyMailchimp::class, [errorPolicyRejection(ErrorPolicyMailchimp::class)], function($run, $submission, $key, $form): void {
            $start = count(Craft::getLogger()->messages);
            $run();
            $logs = array_values(array_filter(array_slice(Craft::getLogger()->messages, $start), fn($entry) => is_string($entry[0]) && str_contains($entry[0], 'by Integration API Error Handling')));
            expect($logs)->toHaveCount(1);
            expect($logs[0][0])->toContain('Delivery skipped', 'Known subscriber rejections', 'Skip and log warning', $form->handle, 'submission #' . $submission->id, 'HTTP 400')
                ->not->toContain('subscriber@example.test', 'synthetic-policy-secret');
        });
    } finally {
        $settings->logSubmissionValues = $previous;
    }
});
