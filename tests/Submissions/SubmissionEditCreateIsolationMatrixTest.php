<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\controllers\SubmissionsController;
use verbb\formie\elements\Submission;
use verbb\formie\models\ManagedSubmissionRequest;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\services\SubmissionWorkflow;
use Tests\Support\WebRequestTestHelper;

use yii\web\ForbiddenHttpException;

it('keeps create-new and edit-existing submission flows isolated for the same form', function (): void {
    $form = formie()
        ->form(['title' => 'Edit/Create Isolation Matrix'])
        ->singleLineTextField('fullName', ['required' => true])
        ->create();

    $existing = formie()
        ->submission($form)
        ->with(['fullName' => 'Original Existing'])
        ->save();

    $workflow = new SubmissionWorkflow();

    $editingSubmission = Submission::find()->id($existing->id)->status(null)->one();
    $editingSubmission->setFieldValueFromRequest('fullName', 'Edited Existing');

    $editResponse = $workflow->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_EDIT_EXISTING,
        'form' => $form,
        'submission' => $editingSubmission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
    ]));

    $newSubmission = new Submission();
    $newSubmission->setForm($form);
    $newSubmission->setFieldValueFromRequest('fullName', 'Brand New');

    $createResponse = $workflow->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        'form' => $form,
        'submission' => $newSubmission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
    ]));

    $reloadedExisting = Submission::find()->id($existing->id)->status(null)->one();
    $reloadedNew = Submission::find()->id($createResponse->submission->id)->status(null)->one();

    expect($editResponse->success)->toBeTrue()
        ->and($createResponse->success)->toBeTrue()
        ->and($reloadedExisting)->not->toBeNull()
        ->and($reloadedNew)->not->toBeNull()
        ->and($reloadedExisting->id)->toBe($existing->id)
        ->and($reloadedExisting->getFieldValue('fullName'))->toBe('Edited Existing')
        ->and($reloadedNew->id)->not->toBe($existing->id)
        ->and($reloadedNew->getFieldValue('fullName'))->toBe('Brand New');
});

it('isolates draft state keys between edit-existing and create-new contexts for one form render set', function (): void {
    $form = formie()
        ->form(['title' => 'Edit/Create Draft Isolation'])
        ->singleLineTextField('fullName')
        ->create();

    $existing = formie()
        ->submission($form)
        ->with(['fullName' => 'Existing'])
        ->save();

    $submissionDrafts = Formie::$plugin->getSubmissionDrafts();

    $editKey = $submissionDrafts->resolveFormInstanceKey($form, $existing, [
        'scope' => 'submit',
        'instance' => 'render-shared',
    ]);
    $createKey = $submissionDrafts->resolveFormInstanceKey($form, null, [
        'scope' => 'submit',
        'instance' => 'render-shared',
    ]);

    expect($editKey->submissionId)->toBe((int)$existing->id)
        ->and($createKey->submissionId)->toBeNull()
        ->and($editKey->fingerprint)->not->toBe($createKey->fingerprint);
});

it('uses explicit managed submission ids when saving existing submissions', function (): void {
    $form = formie()
        ->form(['title' => 'Managed Edit Existing Id'])
        ->singleLineTextField('fullName')
        ->create();

    $existing = formie()
        ->submission($form)
        ->with(['fullName' => 'Managed Before'])
        ->save();

    $beforeIds = Submission::find()
        ->formId((int)$form->id)
        ->isIncomplete(null)
        ->isSpam(null)
        ->ids();

    WebRequestTestHelper::withWebRequestContext(function () use ($form, $existing): void {
        $form->setSubmission($existing);

        $result = Formie::$plugin->getSubmissionProcessor()->executeManaged(new ManagedSubmissionRequest([
            'handle' => $form->handle,
            'processMode' => SubmissionWorkflow::PROCESS_MODE_EDIT_EXISTING,
            'siteId' => (int)$existing->siteId,
            'submissionId' => (int)$existing->id,
            'submissionEditToken' => $form->getSubmissionEditToken(),
            'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
            'fieldParamNamespace' => 'fields',
        ]));

        expect($result->response->success)->toBeTrue();
    }, [
        'method' => 'POST',
        'bodyParams' => [
            'fields' => [
                'fullName' => 'Managed After',
            ],
        ],
    ]);

    $afterIds = Submission::find()
        ->formId((int)$form->id)
        ->isIncomplete(null)
        ->isSpam(null)
        ->ids();
    $reloaded = Submission::find()->id($existing->id)->isIncomplete(null)->isSpam(null)->one();

    expect($afterIds)->toBe($beforeIds)
        ->and($reloaded)->not->toBeNull()
        ->and($reloaded->getFieldValue('fullName'))->toBe('Managed After');
});

it('rejects anonymous site edits that identify a completed submission by id only', function (): void {
    $form = formie()
        ->form(['title' => 'Managed Edit Existing Token Required'])
        ->singleLineTextField('fullName')
        ->create();

    $existing = formie()
        ->submission($form)
        ->with(['fullName' => 'Token Before'])
        ->save();

    WebRequestTestHelper::withWebRequestContext(function () use ($form, $existing): void {
        expect(fn() => Formie::$plugin->getSubmissionProcessor()->executeManaged(new ManagedSubmissionRequest([
            'handle' => $form->handle,
            'processMode' => SubmissionWorkflow::PROCESS_MODE_EDIT_EXISTING,
            'siteId' => (int)$existing->siteId,
            'submissionId' => (int)$existing->id,
            'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
            'fieldParamNamespace' => 'fields',
        ])))->toThrow(ForbiddenHttpException::class);
    }, [
        'method' => 'POST',
        'bodyParams' => [
            'fields' => [
                'fullName' => 'Token After',
            ],
        ],
    ]);

    $reloaded = Submission::find()->id($existing->id)->isIncomplete(null)->isSpam(null)->one();

    expect($reloaded)->not->toBeNull()
        ->and($reloaded->getFieldValue('fullName'))->toBe('Token Before');
});

it('normalizes CP submission redirects that lost the control panel trigger', function (): void {
    WebRequestTestHelper::withWebRequestContext(function (): void {
        $controller = new SubmissionsController('formie-submissions-test', Craft::$app);
        $method = new ReflectionMethod(SubmissionsController::class, '_normalizeCpSubmissionRedirectUrl');
        $method->setAccessible(true);

        expect($method->invoke($controller, 'https://craft.example.test/formie/submissions/contact/123'))
            ->toBe(craft\helpers\UrlHelper::cpUrl('formie/submissions/contact/123'))
            ->and($method->invoke($controller, 'formie/submissions/contact/123?site=default'))
            ->toBe(craft\helpers\UrlHelper::cpUrl('formie/submissions/contact/123', 'site=default'));
    }, [
        'hostInfo' => 'https://craft.example.test',
        'httpHost' => 'craft.example.test',
    ]);
});

it('keeps CP edit save redirects in the control panel message branch', function (): void {
    $form = formie()
        ->form(['title' => 'CP Edit Message Redirect'])
        ->singleLineTextField('fullName')
        ->create();

    $existing = formie()
        ->submission($form)
        ->with(['fullName' => 'Before Shortcut'])
        ->save();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form, $existing): void {
        $request->setIsCpRequest(true);
        $wrongRedirect = "https://craft.example.test/formie/submissions/{$form->handle}/{$existing->id}";
        $request->setBodyParams([
            'handle' => $form->handle,
            'submissionId' => (int)$existing->id,
            'siteId' => (int)$existing->siteId,
            'redirect' => Craft::$app->getSecurity()->hashData($wrongRedirect),
            'fields' => [
                'fullName' => 'After Shortcut',
            ],
        ]);

        $response = (new SubmissionsController('formie-submissions-test', Craft::$app))->actionSaveSubmission();

        expect($response->getHeaders()->get('Location'))
            ->toBe(craft\helpers\UrlHelper::cpUrl("formie/submissions/{$form->handle}/{$existing->id}"));
    }, [
        'method' => 'POST',
        'hostInfo' => 'https://craft.example.test',
        'httpHost' => 'craft.example.test',
        'requestUri' => "/admin/formie/submissions/{$form->handle}/{$existing->id}",
    ]);
});
