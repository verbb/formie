<?php

declare(strict_types=1);

use craft\db\Query;
use verbb\formie\helpers\Table;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\services\SubmissionWorkflow;

it('keeps side-effect dispatch idempotency stable across submit-action and request-token variants', function (): void {
    $form = formie()
        ->form(['title' => 'Replay Idempotency Matrix'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()->submission($form)->with([
        'fullName' => 'Replay Matrix',
    ])->save();

    $workflow = new SubmissionWorkflow();
    $tokenA = 'token-a-' . uniqid();
    $tokenB = 'token-b-' . uniqid();

    $countForSubmission = static function() use ($submission): int {
        return (int)(new Query())
            ->from(Table::FORMIE_SUBMISSION_WORKFLOW)
            ->where(['submissionId' => $submission->id])
            ->count();
    };

    $workflow->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_PAYMENT_REPLAY,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
        'requestToken' => $tokenA,
    ]));
    $afterFirstSubmit = $countForSubmission();

    $workflow->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_PAYMENT_REPLAY,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SAVE,
        'requestToken' => $tokenA,
    ]));
    $afterSaveAction = $countForSubmission();

    $workflow->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_PAYMENT_REPLAY,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_BACK,
        'requestToken' => $tokenA,
    ]));
    $afterBackAction = $countForSubmission();

    $workflow->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_PAYMENT_REPLAY,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
        'requestToken' => $tokenA,
    ]));
    $afterReplaySameToken = $countForSubmission();

    $workflow->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_PAYMENT_REPLAY,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
        'requestToken' => $tokenB,
    ]));
    $afterReplayNewToken = $countForSubmission();

    expect($afterFirstSubmit)->toBeGreaterThan(0)
        ->and($afterSaveAction)->toBe($afterFirstSubmit)
        ->and($afterBackAction)->toBe($afterFirstSubmit)
        ->and($afterReplaySameToken)->toBe($afterFirstSubmit)
        ->and($afterReplayNewToken)->toBeGreaterThan($afterReplaySameToken);
});
