<?php

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\enums\SubmissionOperation as Operation;
use verbb\formie\enums\SubmissionOutcomeType as Outcome;
use verbb\formie\enums\NavigationIntent;
use verbb\formie\enums\workflow\Stage;
use verbb\formie\enums\workflow\Task;
use verbb\formie\events\RegisterStageTasksEvent;
use verbb\formie\services\SubmissionWorkflow;
use verbb\formie\workflow\TaskDefinition;
use verbb\formie\workflow\WorkflowContext;
use verbb\formie\workflow\WorkflowManifest;
use verbb\formie\workflow\tasks\TaskInterface;
use verbb\formie\workflow\tasks\TaskResult;
use yii\base\Event;

it('has one fixed manifest and exposes exactly the semantic task anchors', function () {
    expect(array_keys(WorkflowManifest::stages()))->toBe(['preflight', 'validate', 'screen', 'persist', 'dispatch', 'finalize']);
    $anchors = [];
    foreach (WorkflowManifest::stages() as $tasks) {
        foreach ($tasks as $task) {
            if ($task->publicAnchor) {
                $anchors[] = $task->id;
            }
        }
    }
    $lockedAnchors = [
        'preflight.resolveNavigationIntent', 'preflight.applySubmissionDefaults', 'preflight.clearHiddenValues',
        'preflight.enforceProgression', 'preflight.resolveTransition', 'preflight.captureMetadata', 'preflight.applyStatusRules',
        'validate.submission', 'screen.evaluateSpam', 'screen.verifyCaptcha', 'persist.submission',
        'persist.processPayment', 'persist.questionnaireResult', 'dispatch.sendNotifications',
        'dispatch.triggerIntegrations', 'dispatch.sendSpamNotifications',
    ];
    expect($anchors)->toBe($lockedAnchors)
        ->and(array_map(fn($task) => $task->value, Task::cases()))->toBe($lockedAnchors);
});

it('resolves cleared-value routing at the public Preflight anchors before validation', function () {
    $form = formie()->form()->multiPage(2)->onPage(1)
        ->singleLineTextField('control', ['defaultValue' => 'hide'])
        ->singleLineTextField('detail', ['enableConditions' => true, 'conditions' => [
            'showRule' => 'show', 'conditionRule' => 'all', 'conditions' => [['field' => 'control', 'condition' => '=', 'value' => 'show']],
        ]])->singleLineTextField('required', ['required' => true])->onPage(2)->singleLineTextField('later')->create();
    $form->getPages()[1]->getPageSettings()->enablePageConditions = true;
    $form->getPages()[1]->getPageSettings()->pageConditions = [
        'showRule' => 'show', 'conditionRule' => 'all', 'conditions' => [['field' => 'detail', 'condition' => '=', 'value' => 'route']],
    ];
    $submission = new Submission(); $submission->setForm($form); $submission->setFieldValue('detail', 'route');
    $probe = new class implements TaskInterface {
        public array $seen = [];
        public function execute(WorkflowContext $context): TaskResult {
            $this->seen = [$context->command->submission->getFieldValue('detail'), $context->nextPage, $context->attemptedCompletion];
            return TaskResult::continue();
        }
    };
    $register = function (RegisterStageTasksEvent $event) use ($probe) {
        if ($event->stage === Stage::PREFLIGHT) {
            $event->insertTaskAfter(Task::PREFLIGHT_RESOLVE_TRANSITION, new TaskDefinition('test.transition', $probe, [Operation::SUBMIT]));
            $event->insertTaskAfter(Task::PREFLIGHT_ENFORCE_PROGRESSION, new TaskDefinition('test.progression', new class implements TaskInterface {
                public function execute(WorkflowContext $context): TaskResult { return TaskResult::continue(); }
            }, [Operation::SUBMIT]));
        }
    };
    $stages = [];
    $observe = function ($event) use (&$stages) { $stages[] = $event->stage; };
    Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_REGISTER_STAGE_TASKS, $register);
    Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_BEFORE_STAGE, $observe);
    try {
        $result = runSubmissionCommand(submissionCommand(['form' => $form, 'submission' => $submission]));
        expect($probe->seen)->toBe(['', null, true])->and($stages)->toBe(['preflight', 'validate'])
            ->and($result->outcome->type)->toBe(Outcome::VALIDATION_FAILED)->and($submission->id)->toBeNull();
    } finally {
        Event::off(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_REGISTER_STAGE_TASKS, $register);
        Event::off(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_BEFORE_STAGE, $observe);
    }
});

it('halts invalid input before screening and allows a typed custom validation task', function () {
    $form = formie()->form()->create();
    $submission = new Submission();
    $seen = [];
    $observe = function ($event) use (&$seen) { $seen[] = $event->stage; };
    $register = function (RegisterStageTasksEvent $event) {
        if ($event->stage === Stage::VALIDATE) {
            $event->insertTaskAfter(Task::VALIDATE_SUBMISSION, new TaskDefinition('test.reject', new class implements TaskInterface {
                public function execute(WorkflowContext $context): TaskResult
                {
                    $context->command->submission->addError('form', 'Rejected by custom validation.');
                    return TaskResult::continue();
                }
            }, [Operation::SUBMIT]));
        }
    };
    Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_REGISTER_STAGE_TASKS, $register);
    Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_BEFORE_STAGE, $observe);
    try {
        $result = runSubmissionCommand(submissionCommand(['form' => $form, 'submission' => $submission]));
        expect($result->outcome->type)->toBe(Outcome::VALIDATION_FAILED)
            ->and($seen)->toBe(['preflight', 'validate'])
            ->and($submission->id)->toBeNull();
    } finally {
        Event::off(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_REGISTER_STAGE_TASKS, $register);
        Event::off(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_BEFORE_STAGE, $observe);
    }
});

it('saves drafts without validation screening or dispatch and rejects stale versions', function () {
    $form = formie()->form()->create();
    $submission = new Submission();
    $seen = [];
    $observe = function ($event) use (&$seen) { $seen[] = $event->stage; };
    Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_BEFORE_STAGE, $observe);
    try {
        $result = runSubmissionCommand(submissionCommand(['form' => $form, 'submission' => $submission, 'operation' => Operation::SAVE_DRAFT, 'navigation' => NavigationIntent::STAY]));
        expect($result->outcome->type)->toBe(Outcome::DRAFT_SAVED)->and($seen)->toBe(['preflight', 'persist', 'finalize']);
        $stale = submissionCommand(['form' => $form, 'submission' => $submission, 'operation' => Operation::REVISE, 'expectedVersion' => 0]);
        expect(Formie::$plugin->getSubmissionProcessor()->executeCommand($stale)->type)->toBe(Outcome::STATE_CONFLICT);
    } finally {
        Event::off(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_BEFORE_STAGE, $observe);
    }
});

it('recovers a completed receipt without running a second mutation and rejects changed input', function () {
    $form = formie()->form()->create();
    $submission = new Submission();
    $attributes = ['form' => $form, 'submission' => $submission, 'operation' => Operation::SAVE_DRAFT, 'navigation' => NavigationIntent::STAY, 'operationId' => 'receipt', 'payload' => ['answer' => 1]];
    $first = Formie::$plugin->getSubmissionProcessor()->executeCommand(submissionCommand($attributes));
    $attributes['submission'] = new Submission();
    $retry = Formie::$plugin->getSubmissionProcessor()->executeCommand(submissionCommand($attributes));
    expect($retry)->toEqual($first);
    $attributes['payload'] = ['answer' => 2];
    expect(Formie::$plugin->getSubmissionProcessor()->executeCommand(submissionCommand($attributes))->type)->toBe(Outcome::STATE_CONFLICT);
});

it('serializes independent duplicate operations and returns the same durable result', function () {
    $form = formie()->form()->create();
    $submission = formie()->submission($form)->save();
    $log = tempnam(sys_get_temp_dir(), 'formie-operation-');
    $workers = [];
    try {
        for ($i = 0; $i < 2; $i++) {
            $process = proc_open([PHP_BINARY, dirname(__DIR__) . '/Support/operation-worker.php', (string)$submission->id, (string)$submission->stateVersion, $log], [
                0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
            ], $pipes);
            if (!is_resource($process)) {
                throw new RuntimeException('Unable to start operation worker.');
            }
            fclose($pipes[0]);
            $workers[] = [$process, $pipes];
        }
        $results = [];
        foreach ($workers as [$process, $pipes]) {
            $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            expect(proc_close($process))->toBe(0, $output);
            $results[] = $output;
        }
        expect($results[0])->toBe($results[1])->toContain('draftSaved:');
        expect(file_get_contents($log))->toBe("mutated\n");
    } finally {
        unlink($log);
    }
});

it('keeps invalid operations retryable with corrected input', function () {
    $form = formie()->form()->singleLineTextField('name', ['required' => true])->create();
    $submission = new Submission();
    $attributes = ['form' => $form, 'submission' => $submission, 'operationId' => 'correctable', 'payload' => ['name' => '']];
    expect(Formie::$plugin->getSubmissionProcessor()->executeCommand(submissionCommand($attributes))->type)->toBe(Outcome::VALIDATION_FAILED);
    $submission->setFieldValue('name', 'Corrected');
    $attributes['payload'] = ['name' => 'Corrected'];
    expect(Formie::$plugin->getSubmissionProcessor()->executeCommand(submissionCommand($attributes))->type)->toBe(Outcome::COMPLETED);
});

it('treats CP creation as permissioned administrative creation without visitor side effects', function () {
    $form = formie()->form()->multiPage(2)->onPage(1)->singleLineTextField('first')->onPage(2)->singleLineTextField('last')->create();
    $seen = [];
    $observe = function ($event) use (&$seen) { $seen[] = $event->stage; };
    Event::on(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_BEFORE_STAGE, $observe);
    try {
        $result = \Tests\Support\WebRequestTestHelper::withWebRequestContext(function () use ($form) {
            Craft::$app->getUser()->setIdentity(\craft\elements\User::find()->admin(true)->one());
            return runManagedSubmission(new \verbb\formie\models\ManagedSubmissionRequest(['handle' => $form->handle]), \verbb\formie\enums\SubmissionAuthorityType::CONTROL_PANEL);
        }, ['method' => 'POST', 'bodyParams' => ['fields' => ['first' => 'Admin', 'last' => 'Created']]]);
        expect($result->response->outcome->type)->toBe(Outcome::COMPLETED)
            ->and($seen)->toBe(['preflight', 'validate', 'persist', 'finalize'])
            ->and($result->command->usesVisitorProgression())->toBeFalse();
    } finally {
        Event::off(SubmissionWorkflow::class, SubmissionWorkflow::EVENT_BEFORE_STAGE, $observe);
    }
});

it('rejects stale posted values before populating or persisting an existing submission', function () {
    $form = formie()->form()->singleLineTextField('name')->create();
    $submission = formie()->submission($form)->with(['name' => 'Original'])->save();
    $result = \Tests\Support\WebRequestTestHelper::withWebRequestContext(function () use ($form, $submission) {
        Craft::$app->getUser()->setIdentity(\craft\elements\User::find()->admin(true)->one());
        return runManagedSubmission(new \verbb\formie\models\ManagedSubmissionRequest([
            'handle' => $form->handle, 'submissionId' => $submission->id,
            'operation' => Operation::REVISE, 'expectedVersion' => 0,
        ]), \verbb\formie\enums\SubmissionAuthorityType::CONTROL_PANEL);
    }, ['method' => 'POST', 'bodyParams' => ['fields' => ['name' => 'Stale overwrite']]]);
    expect($result->response->outcome->type)->toBe(Outcome::STATE_CONFLICT)
        ->and((string)$result->command->submission->getFieldValue('name'))->toBe('Original')
        ->and((string)Submission::find()->id($submission->id)->one()->getFieldValue('name'))->toBe('Original');
});

it('encrypts bounded receipts and retains interrupted operations until their retry horizon expires', function () {
    $form = formie()->form()->create();
    $operations = Formie::$plugin->getSubmissionOperations();
    $command = submissionCommand(['form' => $form, 'submission' => new Submission(), 'operationId' => 'interrupted', 'payload' => ['secret' => 'private']]);
    expect(fn() => $operations->execute($command, fn() => throw new RuntimeException('Interrupted')))->toThrow(RuntimeException::class, 'Interrupted');
    $reran = false;
    $retry = $operations->execute($command, function () use (&$reran) { $reran = true; });
    expect($retry->type)->toBe(Outcome::STATE_CONFLICT)->and($retry->data['reason'])->toBe('operationRequiresReconciliation')->and($reran)->toBeFalse();
    $completed = submissionCommand(['form' => $form, 'submission' => new Submission(), 'operationId' => 'encrypted', 'payload' => []]);
    $outcome = new \verbb\formie\models\SubmissionOutcome(Outcome::REJECTED, data: ['reason' => 'private outcome']);
    $operations->execute($completed, fn() => $outcome);
    $table = \verbb\formie\helpers\Table::FORMIE_SUBMISSION_OPERATIONS;
    $stored = (new \craft\db\Query())->select('outcome')->from($table)->where(['formId' => $form->id, 'state' => 'completed'])->scalar();
    expect($stored)->not->toContain('private outcome')
        ->and($operations->execute($completed, fn() => throw new RuntimeException('Must recover')))->toEqual($outcome);
    Craft::$app->getDb()->createCommand()->update($table, ['expiresAt' => '2000-01-01 00:00:00'], ['formId' => $form->id])->execute();
    expect($operations->prune())->toBeGreaterThanOrEqual(2)
        ->and((new \craft\db\Query())->from($table)->where(['formId' => $form->id])->exists())->toBeFalse();
});

it('binds managed retry identity to multipart contents rather than temporary upload paths', function () {
    $form = formie()->form()->singleLineTextField('name')->create();
    $firstPath = tempnam(sys_get_temp_dir(), 'formie-file-');
    $secondPath = tempnam(sys_get_temp_dir(), 'formie-file-');
    $originalFiles = $_FILES;
    file_put_contents($firstPath, 'first');
    file_put_contents($secondPath, 'first');
    try {
        \Tests\Support\WebRequestTestHelper::withWebRequestContext(function () use ($form, $firstPath, $secondPath) {
            Craft::$app->getUser()->setIdentity(\craft\elements\User::find()->admin(true)->one());
            $_FILES = ['fields' => ['name' => ['attachment' => 'input.txt'], 'tmp_name' => ['attachment' => $firstPath], 'type' => ['attachment' => 'text/plain'], 'size' => ['attachment' => 5], 'error' => ['attachment' => UPLOAD_ERR_OK]]];
            $run = fn() => runManagedSubmission(new \verbb\formie\models\ManagedSubmissionRequest([
                'handle' => $form->handle, 'operationId' => 'multipart-retry',
            ]), \verbb\formie\enums\SubmissionAuthorityType::CONTROL_PANEL)->response->outcome;
            $first = $run();
            $_FILES['fields']['tmp_name']['attachment'] = $secondPath;
            expect($run())->toEqual($first);
            file_put_contents($secondPath, 'other');
            expect($run()->type)->toBe(Outcome::STATE_CONFLICT);
        }, ['method' => 'POST', 'bodyParams' => ['fields' => ['name' => 'Multipart']]]);
    } finally {
        $_FILES = $originalFiles;
        \yii\web\UploadedFile::reset();
        unlink($firstPath);
        unlink($secondPath);
    }
});
