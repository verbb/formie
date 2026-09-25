<?php

declare(strict_types=1);

use craft\db\Query;
use verbb\formie\helpers\Table;
use verbb\formie\models\SubmissionCommand;
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

    runSubmissionCommand(submissionCommand([
        'operation' => \verbb\formie\enums\SubmissionOperation::PAYMENT_REPLAY,
        'form' => $form,
        'submission' => $submission,
        'navigation' => \verbb\formie\enums\NavigationIntent::ADVANCE,
        'operationId' => $tokenA,
    ]));
    $afterFirstSubmit = $countForSubmission();

    runSubmissionCommand(submissionCommand([
        'operation' => \verbb\formie\enums\SubmissionOperation::PAYMENT_REPLAY,
        'form' => $form,
        'submission' => $submission,
        'navigation' => \verbb\formie\enums\NavigationIntent::STAY,
        'operationId' => $tokenA,
    ]));
    $afterSaveAction = $countForSubmission();

    runSubmissionCommand(submissionCommand([
        'operation' => \verbb\formie\enums\SubmissionOperation::PAYMENT_REPLAY,
        'form' => $form,
        'submission' => $submission,
        'navigation' => \verbb\formie\enums\NavigationIntent::BACK,
        'operationId' => $tokenA,
    ]));
    $afterBackAction = $countForSubmission();

    runSubmissionCommand(submissionCommand([
        'operation' => \verbb\formie\enums\SubmissionOperation::PAYMENT_REPLAY,
        'form' => $form,
        'submission' => $submission,
        'navigation' => \verbb\formie\enums\NavigationIntent::ADVANCE,
        'operationId' => $tokenA,
    ]));
    $afterReplaySameToken = $countForSubmission();

    runSubmissionCommand(submissionCommand([
        'operation' => \verbb\formie\enums\SubmissionOperation::PAYMENT_REPLAY,
        'form' => $form,
        'submission' => $submission,
        'navigation' => \verbb\formie\enums\NavigationIntent::ADVANCE,
        'operationId' => $tokenB,
    ]));
    $afterReplayNewToken = $countForSubmission();

    expect($afterFirstSubmit)->toBeGreaterThan(0)
        ->and($afterSaveAction)->toBe($afterFirstSubmit)
        ->and($afterBackAction)->toBe($afterFirstSubmit)
        ->and($afterReplaySameToken)->toBe($afterFirstSubmit)
        ->and($afterReplayNewToken)->toBeGreaterThan($afterReplaySameToken);
});
