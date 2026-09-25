<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use verbb\formie\models\SubmissionCommand;
use verbb\formie\services\SubmissionWorkflow;
use yii\base\Event;

it('fires the expected lifecycle events for each workflow process mode', function (): void {
    $form = formie()
        ->form(['title' => 'Workflow Mode Matrix'])
        ->settings(['disableCaptchas' => true])
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

        $submitResponse = runSubmissionCommand(submissionCommand([
            'operation' => \verbb\formie\enums\SubmissionOperation::SUBMIT,
            'form' => $form,
            'submission' => $submitSubmission,
            'navigation' => \verbb\formie\enums\NavigationIntent::ADVANCE,
        ]));

        $draftSubmission = new Submission();
        $draftSubmission->setForm($form);
        $draftSubmission->setFieldValueFromRequest('fullName', 'Draft Mode');

        $draftResponse = runSubmissionCommand(submissionCommand([
            'operation' => \verbb\formie\enums\SubmissionOperation::SAVE_DRAFT,
            'form' => $form,
            'submission' => $draftSubmission,
            'navigation' => \verbb\formie\enums\NavigationIntent::STAY,
        ]));

        $existing = formie()->submission($form)->with(['fullName' => 'Existing'])->save();
        $existing->setFieldValueFromRequest('fullName', 'Edited Existing');

        $editResponse = runSubmissionCommand(submissionCommand([
            'operation' => \verbb\formie\enums\SubmissionOperation::REVISE,
            'form' => $form,
            'submission' => $existing,
            'navigation' => \verbb\formie\enums\NavigationIntent::ADVANCE,
        ]));

        $replay = formie()->submission($form)->with(['fullName' => 'Replay'])->save();
        $replayResponse = runSubmissionCommand(submissionCommand([
            'operation' => \verbb\formie\enums\SubmissionOperation::PAYMENT_REPLAY,
            'form' => $form,
            'submission' => $replay,
            'navigation' => \verbb\formie\enums\NavigationIntent::ADVANCE,
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
        ->settings(['disableCaptchas' => true])
        ->singleLineTextField('fullName', ['required' => true])
        ->create();

    $process = new SubmissionWorkflow();

    $submitSubmission = new Submission();
    $submitSubmission->setForm($form);
    $submitSubmission->setFieldValueFromRequest('fullName', '');

    $submitResponse = runSubmissionCommand(submissionCommand([
        'operation' => \verbb\formie\enums\SubmissionOperation::SUBMIT,
        'form' => $form,
        'submission' => $submitSubmission,
        'navigation' => \verbb\formie\enums\NavigationIntent::ADVANCE,
    ]));

    $draftSubmission = new Submission();
    $draftSubmission->setForm($form);
    $draftSubmission->setFieldValueFromRequest('fullName', '');

    $draftResponse = runSubmissionCommand(submissionCommand([
        'operation' => \verbb\formie\enums\SubmissionOperation::SAVE_DRAFT,
        'form' => $form,
        'submission' => $draftSubmission,
        'navigation' => \verbb\formie\enums\NavigationIntent::STAY,
    ]));

    expect($submitResponse->success)->toBeFalse()
        ->and($submitResponse->submission->getErrors())->not->toBeEmpty()
        ->and($draftResponse->success)->toBeTrue()
        ->and($draftResponse->submission->isIncomplete)->toBeTrue();
});
