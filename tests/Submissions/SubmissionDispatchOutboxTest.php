<?php

use craft\db\Query;
use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\enums\workflow\Stage;
use verbb\formie\helpers\DeliveryAttempt;
use verbb\formie\helpers\Table;
use verbb\formie\models\IntegrationExecutionContext;
use verbb\formie\models\IntegrationResult;
use verbb\formie\services\SubmissionDispatches;
use verbb\formie\services\SubmissionWorkflow;
use verbb\formie\workflow\WorkflowContext;
use yii\base\Event;

function interruptCompletionBeforeDispatch(): array
{
    $form = formie()->form()->singleLineTextField('message')->settings(['disableCaptchas' => true])->create();
    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValue('message', 'Original delivery');
    $listener = static function ($event) use ($form) {
        if ($event->command->form->id === $form->id && $event->stage === Stage::PERSIST->value) {
            $uid = $event->context->taskState['dispatch.uid'];
            expect(Craft::$app->getMutex()->isAcquired('formie.business-dispatch.' . hash('sha256', $event->command->submission->id . ':' . $uid)))->toBeTrue();
            throw new RuntimeException('Synthetic interruption after completion commit');
        }
    };
    Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_AFTER_STAGE, $listener);
    try {
        expect(fn() => runSubmissionCommand(submissionCommand(['form' => $form, 'submission' => $submission])))
            ->toThrow(RuntimeException::class, 'Synthetic interruption');
    } finally {
        Event::off(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_AFTER_STAGE, $listener);
    }
    $row = (new Query())->from(SubmissionDispatches::TABLE)->where(['submissionId' => $submission->id])->one();
    return [$form, $submission, $row];
}

it('recovers committed completion intent once without repeating persistence or payment stages', function () {
    [$form, $submission, $row] = interruptCompletionBeforeDispatch();
    expect($row['status'])->toBe('ready')->and((bool)$row['schedulingComplete'])->toBeFalse()
        ->and(Submission::find()->id($submission->id)->one()->isIncomplete)->toBeFalse();
    $stages = [];
    $identity = null;
    $listener = static function ($event) use ($form, &$stages, &$identity) {
        if ($event->command->form->id === $form->id) {
            expect(Craft::$app->getDb()->getTransaction()?->isActive ?? false)->toBeFalse();
            $stages[] = $event->stage;
            $identity = DeliveryAttempt::workflowIdentity();
        }
    };
    Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_BEFORE_STAGE, $listener);
    try {
        $service = Formie::$plugin->getSubmissionDispatches();
        $service->resume((int)$submission->id, $row['uid']);
        $service->resume((int)$submission->id, $row['uid']);
        expect($stages)->toBe(['dispatch'])->and($identity)->toBe($row['uid'])
            ->and($service->get((int)$submission->id, $row['uid'])->status)->toBe('completed')
            ->and($service->get((int)$submission->id, $row['uid'])->schedulingComplete)->toBeTrue();
    } finally {
        Event::off(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_BEFORE_STAGE, $listener);
    }
});

it('rolls back completion when its outbox intent cannot commit', function () {
    [$form, $submission] = continuitySubmission();
    $version = $submission->stateVersion;
    $original = Formie::$plugin->getSubmissionDispatches();
    Formie::$plugin->set('submissionDispatches', new class extends SubmissionDispatches {
        public function recordIntent(WorkflowContext $context): ?string {
            parent::recordIntent($context);
            throw new RuntimeException('Synthetic outbox write failure');
        }
    });
    try {
        expect(fn() => runSubmissionCommand(submissionCommand(['form' => $form, 'submission' => $submission])))
            ->toThrow(RuntimeException::class, 'Synthetic outbox write failure');
        $stored = Submission::find()->id($submission->id)->isIncomplete(null)->one();
        expect($stored->isIncomplete)->toBeTrue()->and($stored->stateVersion)->toBe($version)
            ->and((new Query())->from(SubmissionDispatches::TABLE)->where(['submissionId' => $submission->id])->exists())->toBeFalse();
    } finally {
        Formie::$plugin->set('submissionDispatches', $original);
    }
});

it('reuses completion identity for replay with another request identity but separates edits', function () {
    [$form, $submission, $row] = interruptCompletionBeforeDispatch();
    $service = Formie::$plugin->getSubmissionDispatches();
    $service->resume((int)$submission->id, $row['uid']);
    $submission = Submission::find()->id($submission->id)->one();
    $submission->setForm($form);
    runSubmissionCommand(submissionCommand(['form' => $form, 'submission' => $submission, 'operation' => SubmissionOperation::PAYMENT_REPLAY, 'operationId' => 'another-request-' . uniqid()]));
    expect((int)(new Query())->from(SubmissionDispatches::TABLE)->where(['submissionId' => $submission->id, 'kind' => 'completion'])->count())->toBe(1);
    foreach (['First revision', 'Second revision'] as $value) {
        $submission->setFieldValue('message', $value);
        runSubmissionCommand(submissionCommand(['form' => $form, 'submission' => $submission, 'operation' => SubmissionOperation::REVISE]));
    }
    $edits = (new Query())->select('uid')->from(SubmissionDispatches::TABLE)->where(['submissionId' => $submission->id, 'kind' => 'edit'])->column();
    expect($edits)->toHaveCount(2)->and(count(array_unique($edits)))->toBe(2)->and($edits)->not->toContain($row['uid']);
});

it('does not recover changed submission content under an old identity', function () {
    [$form, $submission, $row] = interruptCompletionBeforeDispatch();
    $submission->setFieldValue('message', 'Changed after intent');
    expect(Craft::$app->getElements()->saveElement($submission, false))->toBeTrue();
    $service = Formie::$plugin->getSubmissionDispatches();
    $service->resume((int)$submission->id, $row['uid']);
    $run = $service->get((int)$submission->id, $row['uid']);
    expect($run->status)->toBe('needs-attention')->and($run->failureCode)->toBe('submission_changed')
        ->and($run->schedulingComplete)->toBeFalse();
});

it('distinguishes scheduled running completed failure and uncertain business runs', function () {
    $form = formie()->form()->singleLineTextField('message')->create();
    $submission = formie()->submission($form)->save();
    $attempts = Formie::$plugin->getDeliveryAttempts();
    $runs = Formie::$plugin->getSubmissionDispatches();
    foreach (['success' => IntegrationResult::succeeded(), 'failure' => IntegrationResult::failed(), 'uncertain' => IntegrationResult::unknown()] as $key => $result) {
        $uid = $attempts->prepare(new IntegrationExecutionContext($submission->id, $form->id, 'outbox-fixture', $key), 'test');
        expect($runs->get($submission->id, $key)->status)->toBe('scheduled');
        $attempts->execute($uid, function () use ($submission, $key, $result, $runs) {
            expect($runs->get($submission->id, $key)->status)->toBe('running');
            return $result;
        });
        expect($runs->get($submission->id, $key)->status)->toBe(match ($key) {
            'success' => 'completed', 'failure' => 'completed-with-failures', 'uncertain' => 'needs-attention',
        });
    }
});

it('retains unpublished intent and claims repeated scheduling idempotently', function () {
    [$form, $submission, $row] = interruptCompletionBeforeDispatch();
    $queue = Craft::$app->getQueue();
    $jobs = [];
    $fail = true;
    $capture = static function (\yii\queue\PushEvent $event) use (&$jobs, &$fail, $row) {
        if ($event->job instanceof \verbb\formie\jobs\DispatchSubmission) {
            $event->handled = true;
            if ($event->job->dispatchUid === $row['uid']) {
                if ($fail) {
                    throw new RuntimeException('Synthetic queue publication failure');
                }
                $jobs[] = $event->job;
            }
        }
    };
    $queue->on(\yii\queue\Queue::EVENT_BEFORE_PUSH, $capture);
    try {
        $service = Formie::$plugin->getSubmissionDispatches();
        expect(fn() => $service->recover(500))->toThrow(RuntimeException::class, 'Synthetic queue publication failure');
        expect($service->get($submission->id, $row['uid'])->status)->toBe('ready');
        $fail = false;
        $service->recover(500);
        $service->recover(500);
        expect($jobs)->toHaveCount(1)->and($service->get($submission->id, $row['uid'])->status)->toBe('scheduled');
        expect($jobs[0]->submissionId)->toBe($submission->id)->and($jobs[0]->dispatchUid)->toBe($row['uid']);
        // A lost queue publication is recoverable. An already-published duplicate
        // still cannot repeat the business run when workers eventually execute it.
        Craft::$app->getDb()->createCommand()->update(SubmissionDispatches::TABLE, ['scheduledAt' => gmdate('Y-m-d H:i:s', time() - 601)], ['submissionId' => $submission->id, 'uid' => $row['uid']])->execute();
        $service->recover(500);
        expect($jobs)->toHaveCount(2);
        foreach ($jobs as $job) {
            $service->resume($job->submissionId, $job->dispatchUid);
        }
        expect($service->get($submission->id, $row['uid'])->status)->toBe('completed');
    } finally {
        $queue->off(\yii\queue\Queue::EVENT_BEFORE_PUSH, $capture);
    }
});

it('serializes independent outbox recovery workers', function () {
    [$form, $submission, $row] = interruptCompletionBeforeDispatch();
    $log = tempnam(sys_get_temp_dir(), 'formie-outbox-');
    $workers = [];
    try {
        for ($i = 0; $i < 2; $i++) {
            $process = proc_open([PHP_BINARY, dirname(__DIR__) . '/Support/outbox-worker.php', (string)$submission->id, $row['uid'], $log], [
                0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
            ], $pipes);
            if (!is_resource($process)) {
                throw new RuntimeException('Unable to start outbox worker.');
            }
            fclose($pipes[0]);
            $workers[] = [$process, $pipes];
        }
        foreach ($workers as [$process, $pipes]) {
            $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $this->assertSame(0, proc_close($process), $output);
        }
        expect(file_get_contents($log))->toBe("dispatch\n")
            ->and(Formie::$plugin->getSubmissionDispatches()->get($submission->id, $row['uid'])->status)->toBe('completed');
    } finally {
        foreach ($workers as [$process, $pipes]) {
            if (is_resource($process)) {
                proc_terminate($process);
                proc_close($process);
            }
        }
        unlink($log);
    }
});
