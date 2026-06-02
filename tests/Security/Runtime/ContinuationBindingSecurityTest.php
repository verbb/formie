<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\models\ManagedSubmissionRequest;
use verbb\formie\services\SubmissionDrafts;
use verbb\formie\services\SubmissionWorkflow;
use Tests\Support\WebRequestTestHelper;

use yii\web\ForbiddenHttpException;

it('does not resolve raw submission uids as continuation credentials', function (): void {
    $formA = formie()
        ->form(['title' => 'Continuation Security A'])
        ->singleLineTextField('fullName')
        ->create();

    $formB = formie()
        ->form(['title' => 'Continuation Security B'])
        ->singleLineTextField('fullName')
        ->create();

    $submissionB = formie()
        ->submission($formB)
        ->with(['fullName' => 'Wrong Form'])
        ->save();

    $submissionB->isIncomplete = true;
    expect(Craft::$app->getElements()->saveElement($submissionB))->toBeTrue();

    expect(Formie::$plugin->getSubmissionProcessor()->resolveContinuationSubmission($formA, null, (string)$submissionB->uid))
        ->toBeNull()
        ->and(Formie::$plugin->getSubmissionProcessor()->resolveContinuationSubmission($formB, null, (string)$submissionB->uid))
        ->toBeNull();
})->group('security');

it('does not treat raw runtime submission uids as anonymous continuation credentials', function (): void {
    $form = formie()
        ->form(['title' => 'Runtime Continuation Bearer Security'])
        ->singleLineTextField('fullName')
        ->create();
    $submission = formie()
        ->submission($form)
        ->with(['fullName' => 'Victim Draft'])
        ->save();

    $submission->isIncomplete = true;
    expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();

    expect(Formie::$plugin->getSubmissionProcessor()->resolveClientContinuationSubmission($form, null, [
        'submissionUid' => (string)$submission->uid,
    ]))->toBeNull();
})->group('security');

it('resolves runtime continuation only when presented with a valid continuation token', function (): void {
    $form = formie()
        ->form(['title' => 'Runtime Continuation Token Security'])
        ->singleLineTextField('fullName')
        ->create();
    $submission = formie()
        ->submission($form)
        ->with(['fullName' => 'Token Draft'])
        ->save();

    $submission->isIncomplete = true;
    expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();
    Formie::$plugin->getSubmissionDrafts()->upsertProgressState($form, $submission, $form->getCurrentPage()?->id);
    $resolved = Formie::$plugin->getSubmissionProcessor()->resolveClientContinuationSubmission($form, null, [
        'continuationToken' => Formie::$plugin->getSubmissionDrafts()->issueResumeToken(
            Formie::$plugin->getSubmissionDrafts()->getProgressState($form),
            [SubmissionDrafts::RESUME_CAPABILITY_UPDATE]
        )->token,
    ]);

    expect($resolved?->uid)->toBe((string)$submission->uid);
})->group('security');

it('rejects managed site submissions that try to continue by raw submission id alone', function (): void {
    $form = formie()
        ->form(['title' => 'Managed Raw Id Continuation Security'])
        ->singleLineTextField('fullName')
        ->create();
    $submission = formie()
        ->submission($form)
        ->with(['fullName' => 'Victim Draft'])
        ->save();

    $submission->isIncomplete = true;
    expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();

    WebRequestTestHelper::withWebRequestContext(function () use ($form, $submission): void {
        expect(fn() => Formie::$plugin->getSubmissionProcessor()->executeManaged(new ManagedSubmissionRequest([
            'handle' => $form->handle,
            'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
            'siteId' => (int)$submission->siteId,
            'submissionId' => (int)$submission->id,
            'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
            'fieldParamNamespace' => 'fields',
        ])))->toThrow(ForbiddenHttpException::class);
    }, [
        'method' => 'POST',
        'bodyParams' => [
            'fields' => [
                'fullName' => 'Attacker Rewrite',
            ],
        ],
    ]);
})->group('security');

it('rejects managed site submissions that try to continue by raw submission uid alone', function (): void {
    $form = formie()
        ->form(['title' => 'Managed Raw Uid Continuation Security'])
        ->singleLineTextField('fullName')
        ->create();
    $submission = formie()
        ->submission($form)
        ->with(['fullName' => 'Victim Draft'])
        ->save();

    $submission->isIncomplete = true;
    expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();

    WebRequestTestHelper::withWebRequestContext(function () use ($form, $submission): void {
        $result = Formie::$plugin->getSubmissionProcessor()->executeManaged(new ManagedSubmissionRequest([
            'handle' => $form->handle,
            'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
            'siteId' => (int)$submission->siteId,
            'submissionUid' => (string)$submission->uid,
            'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
            'fieldParamNamespace' => 'fields',
        ]));

        expect($result->submissionRequest->submission->id)->not->toBe((int)$submission->id)
            ->and($result->submissionRequest->submission->getFieldValue('fullName'))->toBe('Attacker Rewrite');

        $freshSubmission = \verbb\formie\elements\Submission::find()
            ->id((int)$submission->id)
            ->isIncomplete(true)
            ->status(null)
            ->one();

        expect($freshSubmission?->getFieldValue('fullName'))->toBe('Victim Draft');
    }, [
        'method' => 'POST',
        'bodyParams' => [
            'fields' => [
                'fullName' => 'Attacker Rewrite',
            ],
        ],
    ]);
})->group('security');
