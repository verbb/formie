<?php

declare(strict_types=1);

use craft\elements\User;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\SubmissionsController;
use verbb\formie\elements\Submission;
use verbb\formie\enums\SubmissionAuthorityType;
use verbb\formie\models\SubmissionAuthority;
use verbb\formie\services\RuntimeConfiguration;
use verbb\formie\workflow\WorkflowContext;

it('preserves captured hidden metadata during CP edits and permits explicit corrections', function (bool $correct): void {
    $form = formie()->form(['title' => 'CP hidden metadata'])
        ->singleLineTextField('name')
        ->hiddenField('capturedEmail', ['valueSource' => 'userEmail'])
        ->hiddenField('capturedDate', ['valueSource' => 'dateInt'])
        ->hiddenField('campaign', ['defaultValue' => 'direct'])
        ->settings(['disableCaptchas' => true])->create();
    $submission = formie()->submission($form)->with(['name' => 'Original'])->save();
    $submission->setFieldValue('capturedEmail', '');
    $submission->setFieldValue('capturedDate', '01/01/2020');
    expect(Craft::$app->getElements()->saveElement($submission, false))->toBeTrue();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form, $submission, $correct): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        $request->setBodyParams([
            'handle' => $form->handle,
            'submissionId' => $submission->id,
            'expectedVersion' => $submission->stateVersion,
            'siteId' => $submission->siteId,
            'fields' => [
                'name' => 'CP edit',
                'capturedEmail' => $correct ? 'corrected@example.test' : '',
                'capturedDate' => $correct ? '02/01/2020' : '01/01/2020',
                'campaign' => 'cp-campaign',
            ],
        ]);
        (new SubmissionsController('formie-hidden-test', Craft::$app))->actionSaveAdminSubmission();
    }, ['method' => 'POST', 'requestUri' => '/admin/formie/submissions']);

    $saved = Submission::find()->id($submission->id)->status(null)->one();
    expect($saved->getFieldValue('name'))->toBe('CP edit')
        ->and($saved->getFieldValue('capturedEmail'))->toBe($correct ? 'corrected@example.test' : '')
        ->and($saved->getFieldValue('capturedDate'))->toBe($correct ? '02/01/2020' : '01/01/2020')
        ->and($saved->getFieldValue('campaign'))->toBe('cp-campaign');
})->with([false, true]);

it('keeps hidden sources authoritative for visitor commands even in a CP request', function (): void {
    $form = formie()->form()->hiddenField('capturedEmail', ['valueSource' => 'userEmail'])->create();
    $submission = formie()->submission($form)->save();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form, $submission): void {
        $request->setIsCpRequest(true);
        $user = User::find()->admin(true)->one();
        Craft::$app->getUser()->setIdentity($user);
        $field = $form->getFieldByHandle('capturedEmail');
        expect($field->normalizeValueFromRequest('posted@example.test', $submission))->toBe($user->email);
        $command = submissionCommand([
            'form' => $form, 'submission' => $submission,
            'authority' => new SubmissionAuthority(SubmissionAuthorityType::VISITOR, (int)$form->id, (int)$submission->id, 'test-visitor'),
        ]);
        WorkflowContext::push(new WorkflowContext($command));
        try {
            expect($field->normalizeValueFromRequest('posted@example.test', $submission))->toBe($user->email);
            $submission->setFieldValue('capturedEmail', 'posted@example.test');
            (new RuntimeConfiguration())->applyValues($submission);
            expect($submission->getFieldValue('capturedEmail'))->toBe($user->email);
        } finally {
            WorkflowContext::pop();
        }
    }, ['method' => 'POST']);
    expect(WorkflowContext::current())->toBeNull();
});
