<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;

it('parses submission title format with variables when defaulting title', function (): void {
    $form = formie()
        ->form(['title' => 'Default Title Format Form'])
        ->settings(['submissionTitleFormat' => 'PREFIX-{timestamp}'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = new Submission();
    $submission->setForm($form);

    $title = $form->getDefaultSubmissionTitle($submission);

    expect($title)->toStartWith('PREFIX-')
        ->and(strlen($title))->toBeGreaterThan(strlen('PREFIX-'));
});

it('falls back to date stamp when submission title format parses empty', function (): void {
    $form = formie()
        ->form(['title' => 'Empty Parsed Title Form'])
        ->settings(['submissionTitleFormat' => '{field:nonexistentref}'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = new Submission();
    $submission->setForm($form);

    $title = $form->getDefaultSubmissionTitle($submission);

    expect($title)->toMatch('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/');
});

it('uses legacy date-only default when no submission is passed', function (): void {
    $form = formie()
        ->form(['title' => 'No Submission Arg Form'])
        ->settings(['submissionTitleFormat' => '{timestamp}'])
        ->singleLineTextField('fullName')
        ->create();

    $title = $form->getDefaultSubmissionTitle();

    expect($title)->toMatch('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/');
});
