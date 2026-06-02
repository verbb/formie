<?php

declare(strict_types=1);

use DateTime;
use verbb\formie\elements\Submission;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\services\SubmissionWorkflow;

it('enforces schedule windows for before, active, and expired periods', function (): void {
    $workflow = new SubmissionWorkflow();

    $beforeStart = formie()
        ->form(['title' => 'Schedule Before Start'])
        ->singleLineTextField('fullName', ['required' => true])
        ->create();
    $beforeStart->settings->setAttributes([
        'scheduleForm' => true,
        'scheduleFormStart' => new DateTime('+1 day'),
        'scheduleFormPendingMessage' => 'Pending availability.',
    ], false);
    expect(Craft::$app->getElements()->saveElement($beforeStart))->toBeTrue();

    $beforeSubmission = new Submission();
    $beforeSubmission->setForm($beforeStart);
    $beforeSubmission->setFieldValueFromRequest('fullName', 'Before');

    $beforeResponse = $workflow->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        'form' => $beforeStart,
        'submission' => $beforeSubmission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
    ]));

    $active = formie()
        ->form(['title' => 'Schedule Active'])
        ->singleLineTextField('fullName', ['required' => true])
        ->create();
    $active->settings->setAttributes([
        'scheduleForm' => true,
        'scheduleFormStart' => new DateTime('-1 day'),
        'scheduleFormEnd' => new DateTime('+1 day'),
    ], false);
    expect(Craft::$app->getElements()->saveElement($active))->toBeTrue();

    $activeSubmission = new Submission();
    $activeSubmission->setForm($active);
    $activeSubmission->setFieldValueFromRequest('fullName', 'Active');

    $activeResponse = $workflow->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        'form' => $active,
        'submission' => $activeSubmission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
    ]));

    $expired = formie()
        ->form(['title' => 'Schedule Expired'])
        ->singleLineTextField('fullName', ['required' => true])
        ->create();
    $expired->settings->setAttributes([
        'scheduleForm' => true,
        'scheduleFormEnd' => new DateTime('-1 day'),
        'scheduleFormExpiredMessage' => 'Expired availability.',
    ], false);
    expect(Craft::$app->getElements()->saveElement($expired))->toBeTrue();

    $expiredSubmission = new Submission();
    $expiredSubmission->setForm($expired);
    $expiredSubmission->setFieldValueFromRequest('fullName', 'Expired');

    $expiredResponse = $workflow->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        'form' => $expired,
        'submission' => $expiredSubmission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
    ]));

    expect($beforeResponse->success)->toBeFalse()
        ->and(json_encode($beforeResponse->submission->getErrors()))->toContain('not available')
        ->and($activeResponse->success)->toBeTrue()
        ->and($expiredResponse->success)->toBeFalse()
        ->and(json_encode($expiredResponse->submission->getErrors()))->toContain('not available');
});

it('exposes pending and expired schedule messages based on current schedule state', function (): void {
    $pendingForm = formie()
        ->form(['title' => 'Pending Message Contract'])
        ->singleLineTextField('fullName')
        ->create();
    $pendingForm->settings->setAttributes([
        'scheduleForm' => true,
        'scheduleFormStart' => new DateTime('+1 day'),
        'scheduleFormPendingMessage' => 'PENDING_WINDOW_MESSAGE',
    ], false);
    expect(Craft::$app->getElements()->saveElement($pendingForm))->toBeTrue();

    $expiredForm = formie()
        ->form(['title' => 'Expired Message Contract'])
        ->singleLineTextField('fullName')
        ->create();
    $expiredForm->settings->setAttributes([
        'scheduleForm' => true,
        'scheduleFormEnd' => new DateTime('-1 day'),
        'scheduleFormExpiredMessage' => 'EXPIRED_WINDOW_MESSAGE',
    ], false);
    expect(Craft::$app->getElements()->saveElement($expiredForm))->toBeTrue();

    expect($pendingForm->isBeforeSchedule())->toBeTrue()
        ->and(strip_tags($pendingForm->settings->getScheduleFormPendingMessage()))->toContain('PENDING_WINDOW_MESSAGE')
        ->and($expiredForm->isAfterSchedule())->toBeTrue()
        ->and(strip_tags($expiredForm->settings->getScheduleFormExpiredMessage()))->toContain('EXPIRED_WINDOW_MESSAGE');
});
