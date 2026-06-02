<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\services\SubmissionWorkflow;
use yii\base\Event;

it('fires the expected lifecycle events for each workflow process mode', function (): void {
    $form = formie()
        ->form(['title' => 'Workflow Mode Matrix'])
        ->singleLineTextField('fullName', ['required' => true])
        ->create();

    $process = new SubmissionWorkflow();
    $events = [
        SubmissionWorkflow::EVENT_BEFORE_STAGE,
        SubmissionWorkflow::EVENT_AFTER_STAGE,
    ];

    $fired = [];
    $handlers = [];

    foreach ($events as $eventName) {
        $handlers[$eventName] = static function() use (&$fired, $eventName): void {
            $fired[] = $eventName;
        };

        Event::on(SubmissionWorkflow::class, $eventName, $handlers[$eventName]);
    }

    try {
        $submitSubmission = new Submission();
        $submitSubmission->setForm($form);
        $submitSubmission->setFieldValueFromRequest('fullName', 'Submit Mode');

        $submitResponse = $process->processSubmissionRequest(new SubmissionRequest([
            'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
            'form' => $form,
            'submission' => $submitSubmission,
            'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
        ]));

        $draftSubmission = new Submission();
        $draftSubmission->setForm($form);
        $draftSubmission->setFieldValueFromRequest('fullName', 'Draft Mode');

        $draftResponse = $process->processSubmissionRequest(new SubmissionRequest([
            'processMode' => SubmissionWorkflow::PROCESS_MODE_SAVE_DRAFT,
            'form' => $form,
            'submission' => $draftSubmission,
            'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SAVE,
        ]));

        $existing = formie()->submission($form)->with(['fullName' => 'Existing'])->save();
        $existing->setFieldValueFromRequest('fullName', 'Edited Existing');

        $editResponse = $process->processSubmissionRequest(new SubmissionRequest([
            'processMode' => SubmissionWorkflow::PROCESS_MODE_EDIT_EXISTING,
            'form' => $form,
            'submission' => $existing,
            'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
        ]));

        $replay = formie()->submission($form)->with(['fullName' => 'Replay'])->save();
        $replayResponse = $process->processSubmissionRequest(new SubmissionRequest([
            'processMode' => SubmissionWorkflow::PROCESS_MODE_PAYMENT_REPLAY,
            'form' => $form,
            'submission' => $replay,
            'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
        ]));

        expect($submitResponse->success)->toBeTrue()
            ->and($draftResponse->success)->toBeTrue()
            ->and($editResponse->success)->toBeTrue()
            ->and($replayResponse->success)->toBeTrue()
            ->and($fired)->toContain(SubmissionWorkflow::EVENT_BEFORE_STAGE)
            ->and($fired)->toContain(SubmissionWorkflow::EVENT_AFTER_STAGE);
    } finally {
        foreach ($handlers as $eventName => $handler) {
            Event::off(SubmissionWorkflow::class, $eventName, $handler);
        }
    }
});

it('enforces validation for submit mode while allowing save-draft mode bypass', function (): void {
    $form = formie()
        ->form(['title' => 'Workflow Validation Matrix'])
        ->singleLineTextField('fullName', ['required' => true])
        ->create();

    $process = new SubmissionWorkflow();

    $submitSubmission = new Submission();
    $submitSubmission->setForm($form);
    $submitSubmission->setFieldValueFromRequest('fullName', '');

    $submitResponse = $process->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        'form' => $form,
        'submission' => $submitSubmission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
    ]));

    $draftSubmission = new Submission();
    $draftSubmission->setForm($form);
    $draftSubmission->setFieldValueFromRequest('fullName', '');

    $draftResponse = $process->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_SAVE_DRAFT,
        'form' => $form,
        'submission' => $draftSubmission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SAVE,
    ]));

    expect($submitResponse->success)->toBeFalse()
        ->and($submitResponse->submission->getErrors())->not->toBeEmpty()
        ->and($draftResponse->success)->toBeTrue()
        ->and($draftResponse->submission->isIncomplete)->toBeTrue();
});
