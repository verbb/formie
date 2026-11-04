<?php

use craft\db\Query;
use craft\helpers\Db;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\enums\IntegrationStatus;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\events\ModifyFormIntegrationsEvent;
use verbb\formie\events\SendIntegrationPayloadEvent;
use verbb\formie\integrations\helpdesk\Freshdesk;
use verbb\formie\jobs\TriggerIntegration;
use verbb\formie\models\IntegrationResult;
use verbb\formie\services\DeliveryAttempts;
use verbb\formie\services\Integrations;
use yii\base\Event;

class PassOrFailFreshdesk extends Freshdesk
{
    public static array $requests = [];
    public static HandlerStack $handler;

    protected function createDeliveryHttpHandler(): callable { return self::$handler; }
    public function validate($attributeNames = null, $clearErrors = true): bool { return true; }
    public function getFieldMappingValues($submission, $mapping, $settings = []) { return ['name' => 'Contact', 'email' => 'contact@example.test']; }
}

function passOrFailProvider(array $responses): PassOrFailFreshdesk
{
    PassOrFailFreshdesk::$requests = [];
    PassOrFailFreshdesk::$handler = HandlerStack::create(new MockHandler($responses));
    PassOrFailFreshdesk::$handler->push(Middleware::history(PassOrFailFreshdesk::$requests));

    return new PassOrFailFreshdesk([
        'name' => 'Freshdesk', 'handle' => 'passOrFail', 'enabled' => true,
        'apiDomain' => 'https://8.8.8.8', 'apiKey' => 'synthetic-key',
        'mapToContact' => true, 'mapToTicket' => true, 'contactFieldMapping' => [], 'ticketFieldMapping' => [],
    ]);
}

function passOrFailPaths(): array
{
    return array_map(fn($entry) => $entry['request']->getMethod() . ' ' . $entry['request']->getUri()->getPath(), PassOrFailFreshdesk::$requests);
}

function passOrFailQueuedRun(Integration $integration, callable $callback): void
{
    $form = formie()->form()->singleLineTextField('name')->create();
    $submission = formie()->submission($form)->save();
    $listener = function(ModifyFormIntegrationsEvent $event) use ($form, $integration): void {
        if ($event->form->id === $form->id) {
            $event->integrations[] = $integration;
        }
    };
    Event::on(Integrations::class, Integrations::EVENT_MODIFY_FORM_INTEGRATIONS, $listener);

    try {
        Formie::$plugin->getIntegrationRunner()->queueSteps($submission, [$integration->handle], SubmissionOperation::SUBMIT, ['triggerEvent' => 'submit'], executionKey: 'pass-or-fail');
        $uid = (new Query())->select('uid')->from(DeliveryAttempts::TABLE)->where(['submissionId' => $submission->id, 'step' => 'dispatch'])->scalar();
        $callback(new TriggerIntegration(['deliveryAttemptUid' => $uid]), $uid, $submission);
    } finally {
        Event::off(Integrations::class, Integrations::EVENT_MODIFY_FORM_INTEGRATIONS, $listener);
    }
}

function passOrFailLost(): ConnectException
{
    return new ConnectException('Response lost', new Request('POST', 'https://8.8.8.8/api/v2/contacts'));
}

it('sends a failed delivery again when its queue job is retried', function(mixed $failure): void {
    $failure = $failure === 'lost' ? passOrFailLost() : new Response($failure, [], '{"message":"Nope"}');
    $integration = passOrFailProvider([$failure, new Response(201, [], '{"id":1}'), new Response(201, [], '{"id":2}')]);

    passOrFailQueuedRun($integration, function(TriggerIntegration $job, string $uid): void {
        expect(fn() => $job->execute(Craft::$app->getQueue()))->toThrow(RuntimeException::class, 'Integration delivery failed.');
        expect(Formie::$plugin->getDeliveryAttempts()->get($uid)['status'])->toBe('failed');

        $job->execute(Craft::$app->getQueue());

        expect(passOrFailPaths())->toBe(['POST /api/v2/contacts', 'POST /api/v2/contacts', 'POST /api/v2/tickets'])
            ->and(Formie::$plugin->getDeliveryAttempts()->get($uid)['status'])->toBe('succeeded');
    });
})->with([400, 401, 404, 408, 409, 422, 429, 500, 503, 'lost']);

it('skips steps that already succeeded when a job is retried', function(mixed $failure): void {
    $failure = $failure === 'lost' ? passOrFailLost() : new Response($failure, [], '{"message":"Nope"}');
    $integration = passOrFailProvider([new Response(201, [], '{"id":1}'), $failure, new Response(201, [], '{"id":2}')]);

    passOrFailQueuedRun($integration, function(TriggerIntegration $job): void {
        expect(fn() => $job->execute(Craft::$app->getQueue()))->toThrow(RuntimeException::class);

        $job->execute(Craft::$app->getQueue());

        expect(passOrFailPaths())->toBe(['POST /api/v2/contacts', 'POST /api/v2/tickets', 'POST /api/v2/tickets']);
    });
})->with([400, 503, 'lost']);

it('passes when a provider handles an error and carries on', function(): void {
    $conflict = new Response(409, [], json_encode(['errors' => [['field' => 'email', 'code' => 'duplicate_value', 'additional_info' => ['user_id' => 123]]]]));
    $integration = passOrFailProvider([$conflict, new Response(200, [], '{"id":123}'), new Response(201, [], '{"id":2}')]);

    passOrFailQueuedRun($integration, function(TriggerIntegration $job, string $uid, Submission $submission): void {
        $job->execute(Craft::$app->getQueue());

        expect(passOrFailPaths())->toBe(['POST /api/v2/contacts', 'PUT /api/v2/contacts/123', 'POST /api/v2/tickets'])
            ->and(Formie::$plugin->getDeliveryAttempts()->get($uid)['status'])->toBe('succeeded')
            ->and((new Query())->select('status')->from('{{%formie_submission_dispatches}}')->where(['submissionId' => $submission->id])->scalar())->toBe('completed');
    });
});

it('fails when a hook rejects the provider response', function(): void {
    $integration = passOrFailProvider([new Response(201, [], '{"id":1}'), new Response(201, [], '{"id":2}')]);
    $listener = function(SendIntegrationPayloadEvent $event): void {
        $event->isValid = false;
    };
    Event::on(Integration::class, Integration::EVENT_AFTER_SEND_PAYLOAD, $listener);

    try {
        passOrFailQueuedRun($integration, function(TriggerIntegration $job, string $uid): void {
            expect(fn() => $job->execute(Craft::$app->getQueue()))->toThrow(RuntimeException::class, 'Integration delivery failed.');
            expect(Formie::$plugin->getDeliveryAttempts()->get($uid)['status'])->toBe('failed');
        });
    } finally {
        Event::off(Integration::class, Integration::EVENT_AFTER_SEND_PAYLOAD, $listener);
    }
});

it('still skips a send cancelled by an event', function(): void {
    $integration = passOrFailProvider([]);
    $listener = function(SendIntegrationPayloadEvent $event): void {
        $event->isValid = false;
    };
    Event::on(Integration::class, Integration::EVENT_BEFORE_SEND_PAYLOAD, $listener);

    try {
        passOrFailQueuedRun($integration, function(TriggerIntegration $job, string $uid): void {
            $job->execute(Craft::$app->getQueue());

            expect(passOrFailPaths())->toBe([]);
        });
    } finally {
        Event::off(Integration::class, Integration::EVENT_BEFORE_SEND_PAYLOAD, $listener);
    }
});

it('runs a queued delivery after the submission or settings change', function(): void {
    $integration = passOrFailProvider([new Response(201, [], '{"id":1}'), new Response(201, [], '{"id":2}')]);

    passOrFailQueuedRun($integration, function(TriggerIntegration $job, string $uid, Submission $submission): void {
        $submission->setFieldValue('name', 'Changed after queueing');
        Craft::$app->getElements()->saveElement($submission, false);

        $job->execute(Craft::$app->getQueue());

        expect(passOrFailPaths())->toBe(['POST /api/v2/contacts', 'POST /api/v2/tickets'])
            ->and(Formie::$plugin->getDeliveryAttempts()->get($uid)['status'])->toBe('succeeded');
    });
});

it('uses current settings when a job is retried', function(): void {
    $integration = passOrFailProvider([new Response(400, [], '{"errors":[]}'), new Response(201, [], '{"id":1}')]);

    passOrFailQueuedRun($integration, function(TriggerIntegration $job) use ($integration): void {
        expect(fn() => $job->execute(Craft::$app->getQueue()))->toThrow(RuntimeException::class);

        // Someone corrects the integration between the failure and clicking Retry.
        $integration->mapToTicket = false;

        $job->execute(Craft::$app->getQueue());

        expect(passOrFailPaths())->toBe(['POST /api/v2/contacts', 'POST /api/v2/contacts']);
    });
});

it('retries jobs left unknown, rejected or mid-send by an earlier release', function(string $status): void {
    $integration = passOrFailProvider([new Response(201, [], '{"id":1}'), new Response(201, [], '{"id":2}')]);

    passOrFailQueuedRun($integration, function(TriggerIntegration $job, string $uid) use ($status): void {
        $result = $status === 'sending' ? null : json_encode(['status' => $status, 'code' => 'earlier_release', 'retryable' => false]);
        Db::update(DeliveryAttempts::TABLE, ['status' => $status, 'result' => $result], ['uid' => $uid]);

        $job->execute(Craft::$app->getQueue());

        expect(passOrFailPaths())->toBe(['POST /api/v2/contacts', 'POST /api/v2/tickets'])
            ->and(Formie::$plugin->getDeliveryAttempts()->get($uid)['status'])->toBe('succeeded');
    });
})->with(['unknown', 'rejected', 'failed', 'sending']);

it('sends a completed step again when its stored response has expired', function(): void {
    $integration = passOrFailProvider([new Response(201, [], '{"id":1}'), new Response(503), new Response(201, [], '{"id":1}'), new Response(201, [], '{"id":2}')]);

    passOrFailQueuedRun($integration, function(TriggerIntegration $job, string $uid, Submission $submission): void {
        expect(fn() => $job->execute(Craft::$app->getQueue()))->toThrow(RuntimeException::class);

        Db::update(DeliveryAttempts::TABLE, ['response' => null], ['submissionId' => $submission->id, 'step' => 'POST:' . hash('sha256', 'contacts') . ':1']);

        $job->execute(Craft::$app->getQueue());

        expect(passOrFailPaths())->toBe(['POST /api/v2/contacts', 'POST /api/v2/tickets', 'POST /api/v2/contacts', 'POST /api/v2/tickets']);
    });
});

it('treats every non-success result as a retryable failure', function(IntegrationResult $result, string $code): void {
    expect($result->status)->toBe(IntegrationStatus::Failed)
        ->and($result->retryable)->toBeTrue()
        ->and($result->code)->toBe($code)
        ->and($result->requiresReconciliation())->toBeFalse()
        ->and($result->shouldFailQueue())->toBeTrue()
        ->and(IntegrationResult::fromStorage($result->toStorage())->status)->toBe(IntegrationStatus::Failed);
})->with([
    'rejected' => [fn() => IntegrationResult::rejected('http_400'), 'http_400'],
    'unknown' => [fn() => IntegrationResult::unknown('uncertain'), 'uncertain'],
    'failed' => [fn() => IntegrationResult::failed('broken'), 'broken'],
    'stored unknown' => [fn() => IntegrationResult::fromStorage(['status' => 'unknown', 'code' => 'old']), 'old'],
    'stored rejected' => [fn() => IntegrationResult::fromStorage(['status' => 'rejected', 'code' => 'old']), 'old'],
]);

it('reports the provider status code on failures', function(int $status): void {
    $request = new Request('POST', 'https://8.8.8.8');
    $result = IntegrationResult::fromException(new GuzzleHttp\Exception\RequestException('Nope', $request, new Response($status)));

    expect($result->status)->toBe(IntegrationStatus::Failed)->and($result->code)->toBe('http_' . $status);
})->with([400, 409, 429, 500, 503]);
