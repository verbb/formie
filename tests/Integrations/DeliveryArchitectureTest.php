<?php

use craft\helpers\Json;
use verbb\formie\Formie;
use verbb\formie\compatibility\integrations\IntegrationResultCompatibility;
use verbb\formie\enums\IntegrationStatus;
use verbb\formie\errors\IntegrationStepException;
use verbb\formie\helpers\DeliveryDiagnostics;
use verbb\formie\models\IntegrationBatchResult;
use verbb\formie\models\IntegrationExecutionContext;
use verbb\formie\models\IntegrationResult;
use verbb\formie\models\IntegrationResponse;
use verbb\formie\models\IntegrationDispatchPlan;
use verbb\formie\jobs\TriggerIntegration;
use verbb\formie\services\DeliveryAttempts;

it('normalizes all result states without truthy remote responses', function () {
    expect(IntegrationResultCompatibility::normalize(true)->status)->toBe(IntegrationStatus::Succeeded);
    expect(IntegrationResultCompatibility::normalize(false)->status)->toBe(IntegrationStatus::Failed);
    expect(IntegrationResultCompatibility::normalize(new IntegrationResponse(false))->status)->toBe(IntegrationStatus::Failed);
    expect(IntegrationResultCompatibility::normalize(['success' => true])->status)->toBe(IntegrationStatus::Unknown);
    expect(IntegrationResult::skipped()->status)->toBe(IntegrationStatus::Skipped);
    expect(IntegrationResult::rejected()->status)->toBe(IntegrationStatus::Rejected);
    expect((new IntegrationResult(IntegrationStatus::Unknown, retryable: true))->retryable)->toBeFalse();
    $batch = new IntegrationBatchResult();
    $batch->record('a', IntegrationResult::succeeded());
    $batch->record('b', IntegrationResult::skipped());
    expect($batch->accepts())->toBeTrue();
    $batch->record('c', IntegrationResult::unknown());
    expect($batch->accepts())->toBeFalse()->and($batch->results())->toHaveCount(3);
});

it('replays confirmed child writes without repeating external effects', function () {
    $form = formie()->form(['title' => 'Durable step replay'])->singleLineTextField('name')->create();
    $submission = formie()->submission($form)->with(['name' => 'Person'])->save();
    $context = new IntegrationExecutionContext($submission->id, $form->id, 'test', 'replay');
    $attempts = Formie::$plugin->getDeliveryAttempts();
    $parent = $attempts->prepare($context, 'integration');
    $calls = 0;
    $send = function () use (&$calls) { $calls++; return ['id' => 'remote-123']; };
    $first = $attempts->write($context, 'create-contact', $parent, ['email' => 'person@example.test'], $send);
    $second = $attempts->write($context, 'create-contact', $parent, ['email' => 'person@example.test'], $send);
    expect($first)->toBe($second)->and($second['id'])->toBe('remote-123')->and($calls)->toBe(1);
    expect(fn() => $attempts->write($context, 'create-contact', $parent, ['email' => 'changed@example.test'], $send))->toThrow(IntegrationStepException::class);
    expect($calls)->toBe(1);
});

it('preserves unknown outcomes and never automatically repeats the write', function () {
    $form = formie()->form(['title' => 'Unknown step replay'])->singleLineTextField('name')->create();
    $submission = formie()->submission($form)->with(['name' => 'Person'])->save();
    $context = new IntegrationExecutionContext($submission->id, $form->id, 'test', 'unknown');
    $attempts = Formie::$plugin->getDeliveryAttempts();
    $uid = $attempts->prepare($context, 'write');
    $calls = 0;
    $send = function () use (&$calls) { $calls++; throw new RuntimeException('possible remote acceptance'); };
    expect($attempts->execute($uid, $send)->status)->toBe(IntegrationStatus::Unknown);
    expect($attempts->execute($uid, $send)->status)->toBe(IntegrationStatus::Unknown)->and($calls)->toBe(1);
});

it('retries definite failures while retaining append-only history and thin jobs', function () {
    $form = formie()->form(['title' => 'Safe delivery retry'])->singleLineTextField('name')->create();
    $submission = formie()->submission($form)->with(['name' => 'Person'])->save();
    $context = new IntegrationExecutionContext($submission->id, $form->id, 'test', 'retry');
    $attempts = Formie::$plugin->getDeliveryAttempts();
    $uid = $attempts->prepare($context, 'write');
    $job = new TriggerIntegration(['deliveryAttemptUid' => $uid]);
    $before = serialize($job);
    $attempts->execute($uid, fn() => IntegrationResult::failed('definite', true));
    $attempts->execute($uid, fn() => IntegrationResult::succeeded());
    expect(serialize($job))->toBe($before)->and(strlen($before))->toBeLessThan(1024);
    $bundle = $attempts->supportBundle($uid);
    expect(array_column($bundle['checkpoints'], 'checkpoint'))->toContain('retry');
    expect($bundle['status'])->toBe('succeeded');
});

it('redacts and bounds nested diagnostic projections', function () {
    $encoded = DeliveryDiagnostics::encode(['apiKey' => 'secret', 'nested' => ['Authorization' => 'Bearer secret', 'body' => 'value known-secret']], ['known-secret']);
    expect($encoded)->not->toContain('known-secret', 'Bearer secret', '"secret"');
    expect(strlen(DeliveryDiagnostics::encode(array_fill(0, 500, str_repeat('x', 10000)))))->toBeLessThan(17000);
});

it('encrypts complete checkpoint evidence and redacts credentials before retention', function () {
    $form = formie()->form()->singleLineTextField('name')->create();
    $submission = formie()->submission($form)->save();
    $attempts = Formie::$plugin->getDeliveryAttempts();
    $uid = $attempts->prepare(new IntegrationExecutionContext($submission->id, $form->id, 'evidence', 'encrypted-evidence'), 'integration');
    $attempts->checkpoint($uid, 'request', [
        'request' => [
            'name' => 'Diagnostic Person',
            'nested' => array_fill(0, 125, 'retained-value'),
            'body' => 'known-secret must not survive',
        ],
    ], ['known-secret']);

    $attempt = $attempts->get($uid);
    $stored = (string)(new \craft\db\Query())
        ->select('data')
        ->from($attempts::DIAGNOSTICS)
        ->where(['attemptId' => $attempt['id'], 'checkpoint' => 'request'])
        ->scalar();
    $bundle = Json::encode($attempts->supportBundle($uid));

    expect($stored)->not->toContain('Diagnostic Person', 'known-secret', 'retained-value')
        ->and($bundle)->toContain('Diagnostic Person', 'retained-value')
        ->not->toContain('known-secret')
        ->and(substr_count($bundle, 'retained-value'))->toBe(125);
});

it('migrates stored beta lane and notification timing names deliberately', function () {
    $plan = IntegrationDispatchPlan::fromFormSettings(['enabled' => true, 'notificationTiming' => 'afterIntegrations', 'steps' => [['handle' => 'a', 'mode' => 'immediate'], ['handle' => 'b', 'mode' => 'queued']]]);
    expect($plan->steps)->toBe([['handle' => 'a', 'execution' => 'synchronous'], ['handle' => 'b', 'execution' => 'queued']]);
    expect($plan->notificationTiming)->toBe('afterFinalizedDeliveryAttempts');
});

it('blocks direct provider calls while a previous binding delivery remains unresolved', function () {
    $form = formie()->form()->singleLineTextField('name')->create();
    $submission = formie()->submission($form)->save();
    $attempts = Formie::$plugin->getDeliveryAttempts();
    $uid = $attempts->startDirect(new \verbb\formie\models\IntegrationExecutionContext($submission->id, $form->id, 'directProvider', 'first', 'synchronous'));
    expect($attempts->get($uid)['status'])->toBe('sending');
    expect(fn() => $attempts->startDirect(new \verbb\formie\models\IntegrationExecutionContext($submission->id, $form->id, 'directProvider', 'second', 'synchronous')))->toThrow(\verbb\formie\errors\IntegrationStepException::class);
    $attempts->completeDirect($uid, \verbb\formie\models\IntegrationResult::unknown());
    expect(fn() => $attempts->startDirect(new \verbb\formie\models\IntegrationExecutionContext($submission->id, $form->id, 'directProvider', 'third', 'synchronous')))->toThrow(\verbb\formie\errors\IntegrationStepException::class);
});
