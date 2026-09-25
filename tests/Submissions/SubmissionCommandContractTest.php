<?php

use verbb\formie\elements\Submission;
use verbb\formie\enums\NavigationIntent;
use verbb\formie\enums\SubmissionAuthorityType;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\enums\SubmissionOutcomeType;
use verbb\formie\models\SubmissionAuthority;
use verbb\formie\models\SubmissionCommand;
use verbb\formie\models\SubmissionOutcome;

it('binds a command to explicitly authorized form and submission identities', function () {
    $form = formie()->form()->create();
    $submission = new Submission();
    $submission->setForm($form);
    $authority = new SubmissionAuthority(SubmissionAuthorityType::VISITOR, (int)$form->id, null, 'test-session');
    $command = new SubmissionCommand(SubmissionOperation::SUBMIT, NavigationIntent::ADVANCE, $authority, $form, $submission);

    expect($command->operation)->toBe(SubmissionOperation::SUBMIT)
        ->and($command->navigation)->toBe(NavigationIntent::ADVANCE);

    $wrongAuthority = new SubmissionAuthority(SubmissionAuthorityType::VISITOR, (int)$form->id + 1, null, 'test-session');
    expect(fn() => new SubmissionCommand(SubmissionOperation::SUBMIT, NavigationIntent::ADVANCE, $wrongAuthority, $form, $submission))
        ->toThrow(InvalidArgumentException::class);
    expect(fn() => new SubmissionCommand(SubmissionOperation::REVISE, NavigationIntent::STAY, $authority, $form, $submission))
        ->toThrow(InvalidArgumentException::class);
    expect(fn() => new SubmissionCommand(SubmissionOperation::SUBMIT, NavigationIntent::TARGET, $authority, $form, $submission))
        ->toThrow(InvalidArgumentException::class);
});

it('preserves expected domain outcomes without assigning protocol success', function () {
    $outcome = new SubmissionOutcome(SubmissionOutcomeType::STATE_CONFLICT, 12, 'submission-uid', 4);
    expect($outcome->type)->toBe(SubmissionOutcomeType::STATE_CONFLICT)
        ->and($outcome->version)->toBe(4)
        ->and(property_exists($outcome, 'success'))->toBeFalse();
});
