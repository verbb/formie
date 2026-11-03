<?php

use craft\db\Query;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use verbb\formie\Formie;
use verbb\formie\enums\IntegrationStatus;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\errors\IntegrationStepException;
use verbb\formie\events\ModifyFormIntegrationsEvent;
use verbb\formie\integrations\helpdesk\Freshdesk;
use verbb\formie\jobs\TriggerIntegration;
use verbb\formie\models\IntegrationResult;
use verbb\formie\services\Integrations;
use yii\base\Event;

class FreshdeskDeliveryFixture extends Freshdesk
{
    public static array $requests = [];
    public static HandlerStack $handler;

    protected function createDeliveryHttpHandler(): callable { return self::$handler; }
    public function validate($attributeNames = null, $clearErrors = true): bool { return true; }
    public function getFieldMappingValues($submission, $mapping, $settings = []) { return ['name' => 'Contact', 'email' => 'contact@example.test']; }
}

function freshdeskDeliveryFixture(array $responses, bool $ticket = true): FreshdeskDeliveryFixture
{
    FreshdeskDeliveryFixture::$requests = [];
    FreshdeskDeliveryFixture::$handler = HandlerStack::create(new MockHandler($responses));
    FreshdeskDeliveryFixture::$handler->push(Middleware::history(FreshdeskDeliveryFixture::$requests));

    return new FreshdeskDeliveryFixture([
        'name' => 'Freshdesk', 'handle' => 'freshdeskDelivery', 'enabled' => true,
        'apiDomain' => 'https://8.8.8.8', 'apiKey' => 'synthetic-key',
        'mapToContact' => true, 'mapToTicket' => $ticket, 'contactFieldMapping' => [], 'ticketFieldMapping' => [],
    ]);
}

function freshdeskDuplicateContactResponse(): Response
{
    return new Response(409, [], json_encode([
        'description' => 'Validation failed',
        'errors' => [['field' => 'email', 'code' => 'duplicate_value', 'additional_info' => ['user_id' => 123]]],
    ]));
}

it('completes queued Freshdesk contact fallbacks and replays without sending again', function(string $contact, bool $ticket): void {
    $responses = match ($contact) {
        'new' => [new Response(201, [], '{"id":123}')],
        'existing' => [freshdeskDuplicateContactResponse(), new Response(200, [], '{"id":123}')],
        'legacy duplicate' => [freshdeskDuplicateContactResponse()->withStatus(400), new Response(200, [], '{"id":123}')],
        'deleted' => [freshdeskDuplicateContactResponse(), new Response(405, [], '{"message":"PUT method is not allowed. It should be one of these method(s): GET"}')],
        'agent' => [freshdeskDuplicateContactResponse(), new Response(404)],
    };
    if ($ticket) $responses[] = new Response(201, [], '{"id":456}');
    $integration = freshdeskDeliveryFixture($responses, $ticket);
    $form = formie()->form()->singleLineTextField('name')->create();
    $submission = formie()->submission($form)->save();
    $listener = function(ModifyFormIntegrationsEvent $event) use ($form, $integration): void {
        if ($event->form->id === $form->id) $event->integrations[] = $integration;
    };
    Event::on(Integrations::class, Integrations::EVENT_MODIFY_FORM_INTEGRATIONS, $listener);
    try {
        $runner = Formie::$plugin->getIntegrationRunner();
        $runner->queueSteps($submission, [$integration->handle], SubmissionOperation::SUBMIT, ['triggerEvent' => 'submit'], executionKey: 'freshdesk-fallback');
        $attempts = Formie::$plugin->getDeliveryAttempts();
        $uid = (new Query())->select('uid')->from($attempts::TABLE)->where(['submissionId' => $submission->id, 'step' => 'dispatch'])->scalar();
        $result = $runner->runQueuedAttempt($uid);
        expect($result->status)->toBe(IntegrationStatus::Succeeded)->and($result->shouldFailQueue())->toBeFalse();
        $job = new TriggerIntegration(['deliveryAttemptUid' => $uid]);
        $job->execute(Craft::$app->getQueue());
        expect(FreshdeskDeliveryFixture::$requests)->toHaveCount(count($responses));
        $paths = array_map(fn($entry) => $entry['request']->getMethod() . ' ' . $entry['request']->getUri()->getPath(), FreshdeskDeliveryFixture::$requests);
        $expected = ['POST /api/v2/contacts'];
        if ($contact !== 'new') $expected[] = 'PUT /api/v2/contacts/123';
        if ($ticket) $expected[] = 'POST /api/v2/tickets';
        expect($paths)->toBe($expected);
        $operations = (new Query())->from($attempts::TABLE)->where(['submissionId' => $submission->id])->orderBy(['id' => SORT_ASC])->all();
        expect(array_intersect(array_column($operations, 'status'), ['unknown', 'sending']))->toBeEmpty();
        if ($contact !== 'new') {
            $create = (new Query())->from($attempts::TABLE)->where(['submissionId' => $submission->id, 'step' => 'POST:' . hash('sha256', 'contacts') . ':1'])->one();
            expect($create['status'])->toBe('rejected');
            expect(json_encode($attempts->supportBundle($create['uid'])))->toContain('duplicate_value', 'response-error');
        }
    } finally {
        Event::off(Integrations::class, Integrations::EVENT_MODIFY_FORM_INTEGRATIONS, $listener);
    }
})->with(['new', 'existing', 'legacy duplicate', 'deleted', 'agent'])->with([true, false]);

it('does not resend Freshdesk contact writes when a later ticket can safely retry', function(): void {
    $integration = freshdeskDeliveryFixture([
        freshdeskDuplicateContactResponse(), new Response(200, [], '{"id":123}'),
        new IntegrationStepException(IntegrationResult::failed('confirmed_not_sent', true)),
        new Response(201, [], '{"id":456}'),
    ]);
    $form = formie()->form()->singleLineTextField('name')->create();
    $submission = formie()->submission($form)->save();
    $runner = Formie::$plugin->getIntegrationRunner();
    expect($runner->runIntegration($integration, $submission, 'freshdesk-retry', 'queued')->retryable)->toBeTrue();
    expect($runner->runIntegration($integration, $submission, 'freshdesk-retry', 'queued')->status)->toBe(IntegrationStatus::Succeeded);
    expect(array_map(fn($entry) => $entry['request']->getUri()->getPath(), FreshdeskDeliveryFixture::$requests))
        ->toBe(['/api/v2/contacts', '/api/v2/contacts/123', '/api/v2/tickets', '/api/v2/tickets']);
});

it('does not treat a Freshdesk ticket conflict as an existing contact', function(): void {
    $integration = freshdeskDeliveryFixture([freshdeskDuplicateContactResponse()]);
    $integration->mapToContact = false;
    $form = formie()->form()->singleLineTextField('name')->create();
    $submission = formie()->submission($form)->save();
    $result = Formie::$plugin->getIntegrationRunner()->runIntegration($integration, $submission, 'freshdesk-ticket-conflict', 'queued');
    expect($result->status)->toBe(IntegrationStatus::Unknown)->and(FreshdeskDeliveryFixture::$requests)->toHaveCount(1);
});

it('preserves unhandled Freshdesk failures and blocks uncertain replay', function(string $failure, string $status, int $requests): void {
    $lost = new ConnectException('Response lost', new Request('PUT', 'https://8.8.8.8/api/v2/contacts/123'));
    $responses = match ($failure) {
        'validation' => [new Response(400, [], '{"errors":[{"field":"email","code":"invalid_value"}]}')],
        'missing contact id' => [new Response(409, [], '{"errors":[{"field":"email","code":"duplicate_value"}]}')],
        'unrelated conflict' => [new Response(409, [], '{"errors":[{"field":"phone","code":"duplicate_value","additional_info":{"user_id":123}}]}')],
        'malformed conflict' => [new Response(409, [], 'not json')],
        'update validation' => [freshdeskDuplicateContactResponse(), new Response(400, [], '{"errors":[]}')],
        'unrelated missing resource' => [freshdeskDuplicateContactResponse(), new Response(404, [], '{"message":"Not found"}')],
        'unrelated method' => [freshdeskDuplicateContactResponse(), new Response(405, [], '{"message":"Not allowed"}')],
        'update timeout' => [freshdeskDuplicateContactResponse(), $lost],
        'update server' => [freshdeskDuplicateContactResponse(), new Response(500)],
        'ticket timeout' => [freshdeskDuplicateContactResponse(), new Response(200, [], '{"id":123}'), $lost],
    };
    $integration = freshdeskDeliveryFixture($responses);
    $form = formie()->form()->singleLineTextField('name')->create();
    $submission = formie()->submission($form)->save();
    $runner = Formie::$plugin->getIntegrationRunner();
    $result = $runner->runIntegration($integration, $submission, 'freshdesk-failed', 'queued');
    expect($result->status->value)->toBe($status)->and($result->shouldFailQueue())->toBeTrue();
    expect($runner->runIntegration($integration, $submission, 'freshdesk-failed', 'queued')->status->value)->toBe($status);
    expect(FreshdeskDeliveryFixture::$requests)->toHaveCount($requests);
})->with([
    ['validation', 'rejected', 1], ['missing contact id', 'unknown', 1], ['unrelated conflict', 'unknown', 1], ['malformed conflict', 'unknown', 1],
    ['update validation', 'rejected', 2], ['unrelated missing resource', 'rejected', 2], ['unrelated method', 'rejected', 2],
    ['update timeout', 'unknown', 2], ['update server', 'unknown', 2], ['ticket timeout', 'unknown', 3],
]);
