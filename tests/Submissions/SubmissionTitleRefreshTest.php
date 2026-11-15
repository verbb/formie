<?php

use verbb\formie\elements\Submission;

function savedSubmissionTitle(Submission $submission): string
{
    return (string)(new craft\db\Query())->select('title')->from('{{%elements_sites}}')
        ->where(['elementId' => $submission->id, 'siteId' => $submission->siteId])->scalar();
}

it('fills submission identity tokens in the title once the submission is saved', function (): void {
    $form = formie()->form()
        ->settings(['submissionTitleFormat' => 'Enquiry {submissionId} on {submissionDate}'])
        ->singleLineTextField('name')
        ->create();
    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValue('name', 'Ada');

    runSubmissionCommand(submissionCommand(['form' => $form, 'submission' => $submission]));

    expect($submission->id)->not->toBeNull()
        ->and(savedSubmissionTitle($submission))->toBe('Enquiry ' . $submission->id . ' on ' . $submission->dateCreated->format('Y-m-d H:i:s'))
        ->and($submission->title)->toBe(savedSubmissionTitle($submission));
});

it('updates the title as later pages supply its field values', function (): void {
    $form = formie()->form()
        ->settings(['submissionTitleFormat' => '{field.first} / {field.second}'])
        ->multiPage(2)
        ->onPage(1)->singleLineTextField('first')
        ->onPage(2)->singleLineTextField('second')
        ->create();
    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValue('first', 'One');

    runSubmissionCommand(submissionCommand(['form' => $form, 'submission' => $submission]));

    expect($submission->isIncomplete)->toBeTrue()
        ->and(trim(savedSubmissionTitle($submission)))->toBe('One /');

    $form->setCurrentPage($form->getPages()[1]);
    $submission->setFieldValue('second', 'Two');

    runSubmissionCommand(submissionCommand(['form' => $form, 'submission' => $submission]));

    expect($submission->isIncomplete)->toBeFalse()
        ->and(savedSubmissionTitle($submission))->toBe('One / Two');
});

it('keeps a title typed into a new control panel submission', function (): void {
    $form = formie()->form()
        ->settings(['submissionTitleFormat' => '{field.name}'])
        ->singleLineTextField('name')
        ->create();
    $submission = new Submission(['title' => 'Typed by staff']);
    $submission->setForm($form);
    $submission->setFieldValue('name', 'Ada');

    runSubmissionCommand(submissionCommand(['form' => $form, 'submission' => $submission, 'policy' => verbb\formie\enums\SubmissionPolicy::ADMINISTRATIVE_CREATE]));

    expect(savedSubmissionTitle($submission))->toBe('Typed by staff');
});

it('uses the title format for a new control panel submission with no title', function (): void {
    $form = formie()->form()
        ->settings(['submissionTitleFormat' => 'Enquiry {submissionId}'])
        ->singleLineTextField('name')
        ->create();
    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValue('name', 'Ada');

    runSubmissionCommand(submissionCommand(['form' => $form, 'submission' => $submission, 'policy' => verbb\formie\enums\SubmissionPolicy::ADMINISTRATIVE_CREATE]));

    expect(savedSubmissionTitle($submission))->toBe('Enquiry ' . $submission->id);
});

it('keeps an edited title when a submission is revised', function (): void {
    $form = formie()->form()
        ->settings(['submissionTitleFormat' => '{field.name}'])
        ->singleLineTextField('name')
        ->create();
    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValue('name', 'Ada');
    runSubmissionCommand(submissionCommand(['form' => $form, 'submission' => $submission]));

    $submission = Submission::find()->id($submission->id)->one();
    $submission->title = 'Edited by staff';
    Craft::$app->getElements()->saveElement($submission, false);

    $submission = Submission::find()->id($submission->id)->one();
    $submission->setFieldValue('name', 'Grace');
    runSubmissionCommand(submissionCommand(['form' => $form, 'submission' => $submission, 'operation' => verbb\formie\enums\SubmissionOperation::REVISE]));

    expect(savedSubmissionTitle($submission))->toBe('Edited by staff');
});
