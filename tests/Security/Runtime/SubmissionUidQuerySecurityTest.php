<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\SubmissionsController;
use verbb\formie\elements\Submission;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\Formie;
use verbb\formie\models\HtmlSubmissionRequest;
use yii\web\ForbiddenHttpException;

function submissionUidPattern(string $kind, string $uid): string
{
    return match ($kind) {
        'all' => '*',
        'prefix' => substr($uid, 0, 8) . '*',
        'suffix' => '*' . substr($uid, -12),
        'not' => 'not 00000000-0000-0000-0000-000000000000',
        'list' => '00000000-0000-0000-0000-000000000000, ' . $uid,
        'malformed' => $uid . 'x',
        'missing' => '00000000-0000-0000-0000-000000000000',
    };
}

it('treats UID search expressions as unavailable on public submission actions', function (string $action, string $kind): void {
    $form = formie()->form()->singleLineTextField('name')->create();
    $submission = formie()->submission($form)->with(['name' => 'Original'])->save();
    $submission->isIncomplete = $action !== 'save-submission';
    expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form, $submission, $action, $kind): void {
        $progress = Formie::$plugin->getSubmissionProgress();
        if ($kind === 'missing') {
            $progress->upsertProgressState($form, $submission, $form->getCurrentPage()?->id);
        }
        // Query input takes precedence over a valid ID in the body.
        $request->setQueryParams(['submissionUid' => submissionUidPattern($kind, $submission->uid)]);
        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(),
            'handle' => $form->handle,
            'submissionUid' => $submission->uid,
            'fields' => ['name' => 'Changed'],
        ]);
        $controller = $action === 'server-submit'
            ? new \verbb\formie\controllers\server\SubmissionsController('server/submissions', Formie::$plugin)
            : new SubmissionsController('submissions', Formie::$plugin);
        $response = $controller->runAction($action === 'server-submit' ? 'submit' : $action);
        expect($response->statusCode)->toBe(200)
            ->and($response->data['success'])->toBeFalse()
            ->and($response->data['recoverable'])->toBeTrue()
            ->and($response->data['resetState'])->toBeTrue()
            ->and($progress->getProgressState($form))->toBeNull();
    }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json']]);

    expect(Submission::find()->id($submission->id)->isIncomplete(null)->isSpam(null)->one()->getFieldValue('name'))->toBe('Original');
})->with(['submit', 'save-submission', 'server-submit'])->with(['all', 'prefix', 'suffix', 'not', 'list', 'malformed', 'missing'])->group('security');

it('preserves valid UID edits and still requires an edit grant', function (string $representation, bool $authorized): void {
    $form = formie()->form()->settings(['disableCaptchas' => true])->singleLineTextField('name')->create();
    $submission = formie()->submission($form)->with(['name' => 'Original'])->save();
    $uid = match ($representation) {
        'uppercase' => strtoupper($submission->uid),
        'whitespace' => " \t" . $submission->uid . "\n",
        default => $submission->uid,
    };
    WebRequestTestHelper::withWebRequestContext(function () use ($form, $submission, $uid, $authorized): void {
        $form->setSubmission($submission);
        $input = new HtmlSubmissionRequest([
            'handle' => $form->handle,
            'operation' => SubmissionOperation::REVISE,
            'submissionUid' => $uid,
            'expectedVersion' => $submission->stateVersion,
            'submissionEditToken' => $authorized ? $form->getSubmissionEditToken() : null,
            'submitAction' => 'submit',
            'fieldParamNamespace' => 'fields',
        ]);
        if ($authorized) {
            expect(runHtmlSubmission($input)->response->success)->toBeTrue();
        } else {
            expect(fn() => runHtmlSubmission($input))->toThrow(ForbiddenHttpException::class);
        }
    }, ['method' => 'POST', 'bodyParams' => ['fields' => ['name' => 'Changed']]]);
    expect(Submission::find()->id($submission->id)->isIncomplete(null)->isSpam(null)->one()->getFieldValue('name'))->toBe($authorized ? 'Changed' : 'Original');
})->with(['normal', 'uppercase', 'whitespace'])->with([false, true])->group('security');

it('requires an exact UID to use progress when automatic restore is off', function (): void {
    $form = formie()->form()->settings(['automaticSubmissionState' => false])->create();
    $submission = formie()->submission($form)->save();
    $submission->isIncomplete = true;
    expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();
    WebRequestTestHelper::withWebRequestContext(function () use ($form, $submission): void {
        Formie::$plugin->getSubmissionProgress()->upsertProgressState($form, $submission, $form->getCurrentPage()?->id);
        $progress = Formie::$plugin->getSubmissionProgress()->getProgressState($form);
        $requests = Formie::$plugin->getSubmissionRequests();
        expect($requests->resolveContinuationSubmission($form, $progress, '*'))->toBeNull()
            ->and($requests->resolveContinuationSubmission($form, $progress, $submission->uid)?->id)->toBe($submission->id);
    });
})->group('security');

it('preserves explicit ID and resume grant precedence over an irrelevant UID expression', function (string $identity): void {
    $form = formie()->form()->settings(['disableCaptchas' => true])->singleLineTextField('name')->create();
    $submission = formie()->submission($form)->with(['name' => 'Original'])->save();
    $submission->isIncomplete = true;
    expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();
    WebRequestTestHelper::withWebRequestContext(function () use ($form, $submission, $identity): void {
        Formie::$plugin->getSubmissionProgress()->upsertProgressState($form, $submission, $form->getCurrentPage()?->id);
        $progress = Formie::$plugin->getSubmissionProgress()->getProgressState($form);
        $token = Formie::$plugin->getSubmissionGrants()->issue($submission, \verbb\formie\services\SubmissionGrants::CONTINUE, $progress->id)->token;
        $result = runHtmlSubmission(new HtmlSubmissionRequest([
            'handle' => $form->handle,
            'operation' => SubmissionOperation::SUBMIT,
            'submissionId' => $identity === 'id' ? (int)$submission->id : null,
            'resumeToken' => $identity === 'resume' ? $token : null,
            'submissionUid' => '*',
            'expectedVersion' => $submission->stateVersion,
            'submitAction' => 'submit',
            'fieldParamNamespace' => 'fields',
        ]));
        expect($result->response->success)->toBeTrue()
            ->and($result->response->submission->id)->toBe($submission->id);
    }, ['method' => 'POST', 'bodyParams' => ['fields' => ['name' => 'Changed']]]);
})->with(['id', 'resume'])->group('security');

it('keeps valid foreign and deleted UIDs unavailable', function (bool $deleted): void {
    $form = formie()->form()->create();
    $other = $deleted ? $form : formie()->form()->create();
    $submission = formie()->submission($other)->save();
    $submission->isIncomplete = true;
    expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();
    if ($deleted) {
        expect(Craft::$app->getElements()->deleteElement($submission))->toBeTrue();
    }
    WebRequestTestHelper::withWebRequestContext(function () use ($form, $submission): void {
        expect(fn() => runHtmlSubmission(new HtmlSubmissionRequest([
            'handle' => $form->handle,
            'operation' => SubmissionOperation::SUBMIT,
            'submissionUid' => $submission->uid,
        ])))->toThrow(\verbb\formie\errors\SubmissionUnavailableException::class);
    }, ['method' => 'POST']);
})->with([false, true])->group('security');
