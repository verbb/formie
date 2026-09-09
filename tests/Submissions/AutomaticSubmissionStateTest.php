<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\models\ManagedSubmissionRequest;
use verbb\formie\services\SubmissionWorkflow;

it('does not advertise continuation tokens from leftover progress when automatic restore is off', function(): void {
    $form = formie()
        ->form(['title' => 'No Auto Restore Session'])
        ->settings(['automaticSubmissionState' => false])
        ->singleLineTextField('firstName')
        ->create();

    $submission = formie()
        ->submission($form)
        ->with(['firstName' => 'Ben'])
        ->save();

    $submission->isIncomplete = true;
    expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();

    $session = WebRequestTestHelper::withWebRequestContext(function () use ($form, $submission): array {
        Formie::$plugin->getSubmissionDrafts()->upsertProgressState($form, $submission, $form->getCurrentPage()?->id);

        return Formie::$plugin->getClientSessionService()->issueInitialSession($form)->toArrayRecursive();
    }, [
        'method' => 'GET',
    ]);

    expect($session['continuation']['continuationToken'] ?? null)->toBeNull();
});

it('starts a new managed submission instead of bare progress when automatic restore is off', function(): void {
    $form = formie()
        ->form(['title' => 'No Auto Restore Submit'])
        ->settings(['automaticSubmissionState' => false, 'disableCaptchas' => true])
        ->multiPage(2)
        ->onPage(1)
        ->singleLineTextField('firstName')
        ->onPage(2)
        ->singleLineTextField('lastName')
        ->create();

    $pages = $form->getPages();
    $prior = formie()
        ->submission($form)
        ->with(['firstName' => 'Prior'])
        ->save();
    $prior->isIncomplete = true;
    expect(Craft::$app->getElements()->saveElement($prior))->toBeTrue();

    $result = WebRequestTestHelper::withWebRequestContext(function () use ($form, $pages, $prior) {
        Formie::$plugin->getSubmissionDrafts()->upsertProgressState($form, $prior, $pages[1]->id);

        return Formie::$plugin->getSubmissionProcessor()->executeManaged(new ManagedSubmissionRequest([
            'handle' => $form->handle,
            'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
            'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
            'pageId' => (int)$pages[0]->id,
            'fieldParamNamespace' => 'fields',
        ]));
    }, [
        'method' => 'POST',
        'bodyParams' => [
            'fields' => [
                'firstName' => 'Fresh',
            ],
        ],
    ]);

    $submission = $result->response->submission;

    expect($result->response->success)->toBeTrue()
        ->and($submission->id)->not->toBe($prior->id)
        ->and((string)$submission->getFieldValue('firstName'))->toBe('Fresh');
});

it('continues the same managed submission when submissionUid matches progress with automatic restore off', function(): void {
    $form = formie()
        ->form(['title' => 'Same Visit Multi Page'])
        ->settings(['automaticSubmissionState' => false, 'disableCaptchas' => true])
        ->multiPage(2)
        ->onPage(1)
        ->singleLineTextField('firstName')
        ->onPage(2)
        ->singleLineTextField('lastName')
        ->create();

    $pages = $form->getPages();
    $submission = formie()
        ->submission($form)
        ->with(['firstName' => 'Ben'])
        ->save();
    $submission->isIncomplete = true;
    expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();

    $result = WebRequestTestHelper::withWebRequestContext(function () use ($form, $pages, $submission) {
        Formie::$plugin->getSubmissionDrafts()->upsertProgressState($form, $submission, $pages[0]->id);

        return Formie::$plugin->getSubmissionProcessor()->executeManaged(new ManagedSubmissionRequest([
            'handle' => $form->handle,
            'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
            'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
            'pageId' => (int)$pages[0]->id,
            'submissionUid' => $submission->uid,
            'fieldParamNamespace' => 'fields',
        ]));
    }, [
        'method' => 'POST',
        'bodyParams' => [
            'fields' => [
                'firstName' => 'Ben Updated',
            ],
        ],
    ]);

    expect($result->response->success)->toBeTrue()
        ->and($result->response->submission->id)->toBe($submission->id)
        ->and((string)$result->response->submission->getFieldValue('firstName'))->toBe('Ben Updated');
});
