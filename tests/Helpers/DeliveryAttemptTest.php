<?php

declare(strict_types=1);

use craft\db\Query;
use craft\helpers\Json;
use verbb\formie\helpers\DeliveryAttempt;
use verbb\formie\helpers\Table;

function deliveryTestSubmission(): \verbb\formie\elements\Submission
{
    $form = formie()->form()->singleLineTextField('fullName')->create();
    return formie()->submission($form)->with(['fullName' => 'Delivery'])->save();
}

it('reuses the provider key after a lost response and reconciles an accepted resource', function (): void {
    $submission = deliveryTestSubmission();
    $keys = [];
    $payload = ['amount' => 100, 'token' => 'test-only-sensitive-value'];
    $attempt = new DeliveryAttempt($submission->id, 'create', 'intended-operation');
    $lostResponse = function ($key) use (&$keys) {
        $keys[] = $key;
        throw new RuntimeException('Response lost');
    };
    expect(fn() => $attempt->execute($payload, $lostResponse, 3600))->toThrow(RuntimeException::class);
    $send = function ($key) use (&$keys): array { $keys[] = $key; return ['id' => 'external-1']; };
    $get = fn($id) => ['id' => $id, 'restored' => true];
    $result = $attempt->execute($payload, $send, 3600, fn($result) => $result['id'], $get);
    expect($result['id'])->toBe('external-1')->and($keys[0])->toBe($keys[1]);
    expect($attempt->execute($payload, $send, 3600, fn($result) => $result['id'], $get)['restored'])->toBeTrue();
    expect($keys)->toHaveCount(2);
    $raw = (new Query())->select('meta')->from(Table::FORMIE_SUBMISSION_WORKFLOW)->where(['submissionId' => $submission->id])->scalar();
    expect($raw)->not->toContain('test-only-sensitive-value')->and($raw)->not->toContain('"amount"');
});

it('blocks uncertain delivery without provider protection and allows an explicit new operation', function (): void {
    $submission = deliveryTestSubmission();
    $attempt = new DeliveryAttempt($submission->id, 'integration', 'first-run');
    expect(fn() => $attempt->execute([], fn() => throw new RuntimeException('No response')))->toThrow(RuntimeException::class);
    $calls = 0;
    $send = function () use (&$calls): bool { $calls++; return true; };
    expect(fn() => $attempt->execute([], $send))->toThrow(RuntimeException::class, 'Delivery outcome unknown');
    expect($calls)->toBe(0);
    expect((new DeliveryAttempt($submission->id, 'integration', 'explicit-rerun'))->execute([], $send))->toBeTrue();
    expect($calls)->toBe(1);
});

it('blocks changed payloads and expired provider protection after an uncertain result', function (): void {
    $submission = deliveryTestSubmission();
    $attempt = new DeliveryAttempt($submission->id, 'payment', 'attempt');
    expect(fn() => $attempt->execute(['amount' => 100], fn() => throw new RuntimeException(), 3600))->toThrow(RuntimeException::class);
    expect(fn() => $attempt->execute(['amount' => 200], fn() => true, 3600))->toThrow(RuntimeException::class, 'parameters changed');
    $row = (new Query())->from(Table::FORMIE_SUBMISSION_WORKFLOW)->where(['submissionId' => $submission->id])->one();
    $meta = Json::decode($row['meta']);
    $meta['startedAt'] -= 3601;
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_SUBMISSION_WORKFLOW, ['meta' => Json::encode($meta)], ['id' => $row['id']])->execute();
    expect(fn() => $attempt->execute(['amount' => 100], fn() => true, 3600))->toThrow(RuntimeException::class, 'Delivery outcome unknown');
});

it('permits a corrected operation only after a confirmed rejection', function (): void {
    $submission = deliveryTestSubmission();
    $attempt = new DeliveryAttempt($submission->id, 'payment', 'attempt');
    $keys = [];
    $reject = function ($key) use (&$keys) {
        $keys[] = $key;
        throw new \GuzzleHttp\Exception\ClientException('Rejected', new \GuzzleHttp\Psr7\Request('POST', 'https://example.test'), new \GuzzleHttp\Psr7\Response(400));
    };
    expect(fn() => $attempt->execute(['amount' => 100], $reject, 3600))->toThrow(\GuzzleHttp\Exception\ClientException::class);
    $attempt->execute(['amount' => 200], function ($key) use (&$keys): bool { $keys[] = $key; return true; }, 3600);
    expect($keys[0])->not->toBe($keys[1]);
});

it('does not send inside a transaction that could roll back its attempt record', function (): void {
    $submission = deliveryTestSubmission();
    $transaction = Craft::$app->getDb()->beginTransaction();
    $calls = 0;
    try {
        $send = function () use (&$calls): bool { $calls++; return true; };
        expect(fn() => (new DeliveryAttempt($submission->id, 'create', 'operation'))->execute([], $send))->toThrow(RuntimeException::class, 'Commit the submission transaction');
        expect($calls)->toBe(0);
    } finally {
        $transaction->rollBack();
    }
});
