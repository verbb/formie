<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\services\SubmissionWorkflow;

dataset('submission_limit_types', ['total', 'day', 'week', 'month', 'year']);

it('enforces submission limits across configured periods', function (string $limitType): void {
    $form = formie()
        ->form(['title' => 'Submission Limit ' . $limitType])
        ->singleLineTextField('fullName', ['required' => true])
        ->create();

    formie()
        ->submission($form)
        ->with(['fullName' => 'Seed Existing'])
        ->save();

    $form->settings->setAttributes([
        'limitSubmissions' => true,
        'limitSubmissionsNumber' => 1,
        'limitSubmissionsType' => $limitType,
        'limitSubmissionsMessage' => 'Limit reached.',
    ], false);
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValueFromRequest('fullName', 'Blocked New');

    $response = (new SubmissionWorkflow())->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
    ]));

    expect($response->success)->toBeFalse()
        ->and(json_encode($response->submission->getErrors()))->toContain('allowed submissions');
})->with('submission_limit_types');

it('allows editing an existing submission even when new submission limits are reached', function (): void {
    $form = formie()
        ->form(['title' => 'Submission Limit Edit Existing'])
        ->singleLineTextField('fullName', ['required' => true])
        ->create();

    $existing = formie()
        ->submission($form)
        ->with(['fullName' => 'Original'])
        ->save();

    $form->settings->setAttributes([
        'limitSubmissions' => true,
        'limitSubmissionsNumber' => 1,
        'limitSubmissionsType' => 'total',
    ], false);
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    $existing->setFieldValueFromRequest('fullName', 'Edited Existing');

    $editResponse = (new SubmissionWorkflow())->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_EDIT_EXISTING,
        'form' => $form,
        'submission' => $existing,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
    ]));

    expect($editResponse->success)->toBeTrue()
        ->and($editResponse->submission->id)->toBe($existing->id)
        ->and($editResponse->submission->getFieldValue('fullName'))->toBe('Edited Existing');
});

it('exposes the configured limit message when submission limits are exceeded', function (): void {
    $form = formie()
        ->form(['title' => 'Limit Message Contract'])
        ->singleLineTextField('fullName')
        ->create();

    $form->settings->setAttributes([
        'limitSubmissions' => true,
        'limitSubmissionsNumber' => 0,
        'limitSubmissionsType' => 'total',
        'limitSubmissionsMessage' => 'LIMIT_EXCEEDED_MESSAGE',
    ], false);
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    expect($form->isWithinSubmissionsLimit())->toBeFalse()
        ->and(strip_tags($form->settings->getLimitSubmissionsMessage()))->toContain('LIMIT_EXCEEDED_MESSAGE');
});
