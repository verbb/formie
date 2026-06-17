<?php

declare(strict_types=1);

use craft\elements\User;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Submission;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\services\SubmissionWorkflow;

function withSubmissionWorkflowContext(callable $callback, array $options = []): mixed
{
    return WebRequestTestHelper::withWebRequestContext($callback, array_merge([
        'method' => 'POST',
    ], $options));
}

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

    $response = withSubmissionWorkflowContext(function () use ($form, $submission) {
        return (new SubmissionWorkflow())->processSubmissionRequest(new SubmissionRequest([
            'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
            'form' => $form,
            'submission' => $submission,
            'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
        ]));
    });

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

    formie()
        ->submission($form)
        ->with(['fullName' => 'Existing Submission'])
        ->save();

    $form->settings->setAttributes([
        'limitSubmissions' => true,
        'limitSubmissionsNumber' => 1,
        'limitSubmissionsType' => 'total',
        'limitSubmissionsMessage' => 'LIMIT_EXCEEDED_MESSAGE',
    ], false);
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    expect($form->isWithinSubmissionsLimit())->toBeFalse()
        ->and($form->isClosedBySubmissionLimit())->toBeTrue()
        ->and(strip_tags($form->settings->getLimitSubmissionsMessage()))->toContain('LIMIT_EXCEEDED_MESSAGE');
});

it('enforces per-ip submission limits without closing the form', function (): void {
    $form = formie()
        ->form(['title' => 'Per IP Submission Limit'])
        ->singleLineTextField('fullName', ['required' => true])
        ->create();

    $existing = formie()
        ->submission($form)
        ->with(['fullName' => 'Existing IP Submission'])
        ->save();
    $existing->ipAddress = '203.0.113.10';
    expect(Craft::$app->getElements()->saveElement($existing))->toBeTrue();

    $form->settings->setAttributes([
        'limitSubmissions' => true,
        'limitSubmissionsScope' => 'ipAddress',
        'limitSubmissionsNumber' => 1,
        'limitSubmissionsType' => 'total',
    ], false);
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    expect($form->isAvailable())->toBeTrue()
        ->and($form->isClosedBySubmissionLimit())->toBeFalse();

    WebRequestTestHelper::withWebRequestContext(function () use ($form): void {
        $submission = new Submission();
        $submission->setForm($form);
        $submission->ipAddress = '203.0.113.10';
        $submission->setFieldValueFromRequest('fullName', 'Blocked IP Submission');

        $response = (new SubmissionWorkflow())->processSubmissionRequest(new SubmissionRequest([
            'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
            'form' => $form,
            'submission' => $submission,
            'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
        ]));

        expect($response->success)->toBeFalse()
            ->and(json_encode($response->submission->getErrors()))->toContain('allowed submissions');
    }, [
        'method' => 'POST',
        'remoteAddr' => '203.0.113.10',
    ]);
});

it('enforces per-user submission limits for logged-in users', function (): void {
    $form = formie()
        ->form(['title' => 'Per User Submission Limit'])
        ->singleLineTextField('fullName', ['required' => true])
        ->create();

    $seedUser = User::find()->status(null)->username('formie-seed-user')->one();
    expect($seedUser)->not->toBeNull();

    $existing = formie()
        ->submission($form)
        ->with(['fullName' => 'Existing User Submission'])
        ->save();
    $existing->userId = (int)$seedUser->id;
    expect(Craft::$app->getElements()->saveElement($existing))->toBeTrue();

    $form->settings->setAttributes([
        'limitSubmissions' => true,
        'limitSubmissionsScope' => 'user',
        'limitSubmissionsNumber' => 1,
        'limitSubmissionsType' => 'total',
        'collectUser' => true,
    ], false);
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    Craft::$app->getUser()->setIdentity($seedUser);

    try {
        expect($form->isAvailable())->toBeTrue()
            ->and($form->isClosedBySubmissionLimit())->toBeFalse();

        $response = withSubmissionWorkflowContext(function () use ($form, $seedUser) {
            Craft::$app->getUser()->setIdentity($seedUser);

            $submission = new Submission();
            $submission->setForm($form);
            $submission->setFieldValueFromRequest('fullName', 'Blocked User Submission');

            return (new SubmissionWorkflow())->processSubmissionRequest(new SubmissionRequest([
                'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
                'form' => $form,
                'submission' => $submission,
                'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
            ]));
        });

        expect($response->success)->toBeFalse()
            ->and(json_encode($response->submission->getErrors()))->toContain('allowed submissions');
    } finally {
        Craft::$app->getUser()->setIdentity(null);
    }
});

it('supports legacy ip-address limit settings via limitSubmissions value', function (): void {
    $form = formie()
        ->form(['title' => 'Legacy IP Limit Settings'])
        ->singleLineTextField('fullName', ['required' => true])
        ->create();

    $existing = formie()
        ->submission($form)
        ->with(['fullName' => 'Legacy Existing'])
        ->save();
    $existing->ipAddress = '203.0.113.55';
    expect(Craft::$app->getElements()->saveElement($existing))->toBeTrue();

    $form->settings->setAttributes([
        'limitSubmissions' => 'ipAddress',
        'limitSubmissionsIpAddressNumber' => 1,
        'limitSubmissionsIpAddressType' => 'total',
    ], false);
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    $blocked = new Submission();
    $blocked->setForm($form);
    $blocked->ipAddress = '203.0.113.55';
    $blocked->setFieldValueFromRequest('fullName', 'Legacy Blocked');

    expect($form->isWithinSubmissionsLimit($blocked))->toBeFalse();
});
