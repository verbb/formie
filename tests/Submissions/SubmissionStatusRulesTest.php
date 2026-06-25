<?php

declare(strict_types=1);

use verbb\formie\conditions\ConditionOperator;
use verbb\formie\Formie;
use verbb\formie\models\SubmissionStatus;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\elements\Submission;
use verbb\formie\services\SubmissionWorkflow;

it('applies matching status rules on final submit', function (): void {
    $status = new SubmissionStatus([
        'name' => 'In Progress',
        'handle' => 'inProgress508',
        'color' => 'orange',
    ]);

    expect(Formie::$plugin->getSubmissionStatuses()->saveStatus($status))->toBeTrue();

    $form = formie()
        ->form(['title' => 'Status Rules Final Submit'])
        ->singleLineTextField('tier')
        ->create();

    $form->settings->enableStatusRules = true;
    $form->settings->statusRules = [[
        'statusId' => $status->id,
        'trigger' => 'finalSubmit',
        'enableConditions' => true,
        'conditions' => [
            'conditionRule' => 'all',
            'conditions' => [[
                'field' => 'tier',
                'condition' => ConditionOperator::EQ,
                'value' => 'vip',
            ]],
        ],
    ]];

    expect(\Craft::$app->elements->saveElement($form))->toBeTrue();

    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValueFromRequest('tier', 'vip');

    $response = (new SubmissionWorkflow())->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
    ]));

    expect($response->success)->toBeTrue()
        ->and($response->submission->statusId)->toBe($status->id);
});

it('applies status rules without conditions when enableConditions is disabled', function (): void {
    $status = new SubmissionStatus([
        'name' => 'Every Page',
        'handle' => 'everyPage508',
        'color' => 'green',
    ]);

    expect(Formie::$plugin->getSubmissionStatuses()->saveStatus($status))->toBeTrue();

    $form = formie()
        ->form(['title' => 'Status Rules Unconditional'])
        ->create();

    $form->settings->enableStatusRules = true;
    $form->settings->statusRules = [[
        'statusId' => $status->id,
        'trigger' => 'everyPage',
        'enableConditions' => false,
    ]];

    expect(\Craft::$app->elements->saveElement($form))->toBeTrue();

    $submission = new Submission();
    $submission->setForm($form);

    $response = (new SubmissionWorkflow())->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SAVE,
    ]));

    expect($response->success)->toBeTrue()
        ->and($response->submission->statusId)->toBe($status->id);
});

it('skips status rules when conditions do not match', function (): void {
    $status = new SubmissionStatus([
        'name' => 'Review',
        'handle' => 'review508',
        'color' => 'blue',
    ]);

    expect(Formie::$plugin->getSubmissionStatuses()->saveStatus($status))->toBeTrue();

    $form = formie()
        ->form(['title' => 'Status Rules No Match'])
        ->singleLineTextField('tier')
        ->create();

    $defaultStatus = $form->getDefaultStatus();
    $form->settings->enableStatusRules = true;
    $form->settings->statusRules = [[
        'statusId' => $status->id,
        'trigger' => 'finalSubmit',
        'enableConditions' => true,
        'conditions' => [
            'conditionRule' => 'all',
            'conditions' => [[
                'field' => 'tier',
                'condition' => ConditionOperator::EQ,
                'value' => 'vip',
            ]],
        ],
    ]];

    expect(\Craft::$app->elements->saveElement($form))->toBeTrue();

    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValueFromRequest('tier', 'standard');

    $response = (new SubmissionWorkflow())->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
    ]));

    expect($response->success)->toBeTrue()
        ->and($response->submission->statusId)->toBe($defaultStatus->id);
});
