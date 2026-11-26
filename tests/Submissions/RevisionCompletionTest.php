<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\SubmissionsController;
use verbb\formie\controllers\server\SubmissionsController as ServerSubmissionsController;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\enums\SubmissionOutcomeType;
use verbb\formie\models\HtmlSubmissionRequest;
use verbb\formie\Formie;
use verbb\formie\services\CompletionResolver;
use verbb\formie\services\RuntimeConfiguration;
use yii\base\Event;

function revisionCompletionFixture(array $settings = []): array
{
    $form = formie()->form()->settings($settings + [
        'disableCaptchas' => true,
        'completionBehavior' => 'message',
        'successMessage' => 'Thanks, check your email for a confirmation.',
    ])->singleLineTextField('name')->create();
    $submission = formie()->submission($form)->with(['name' => 'Original'])->save();

    return [$form, $submission];
}

// Renders the edit form the way a template does, then posts the edit with that render's tokens. With $refresh, the request
// token is first reissued the way the refresh-tokens action does for cached pages, on a form with no submission.
function revisionCompletionEdit(Form $form, Submission $submission, ?callable $template = null, bool $refresh = false): mixed
{
    [$editToken, $requestToken] = WebRequestTestHelper::withWebRequestContext(function () use ($form, $submission, $template): array {
        $rendered = Form::find()->id($form->id)->status(null)->one();
        $rendered->setSubmission($submission);

        if ($template) {
            $template($rendered);
        }

        return [$rendered->getSubmissionEditToken(), $rendered->getRequestToken()];
    });

    if ($refresh) {
        $requestToken = WebRequestTestHelper::withWebRequestContext(function () use ($form, $requestToken): string {
            $refreshed = Form::find()->id($form->id)->status(null)->one();
            (new RuntimeConfiguration())->restoreToken($refreshed, $requestToken);

            return Formie::$plugin->getSubmissionGuards()->issueRequestToken($refreshed);
        });
    }

    return WebRequestTestHelper::withWebRequestContext(fn() => runHtmlSubmission(new HtmlSubmissionRequest([
        'handle' => $form->handle,
        'operation' => SubmissionOperation::REVISE,
        'submissionUid' => $submission->uid,
        'expectedVersion' => (int)$submission->stateVersion,
        'submissionEditToken' => $editToken,
        'requestToken' => $requestToken,
        'submitAction' => 'submit',
        'fieldParamNamespace' => 'fields',
    ])), ['method' => 'POST', 'bodyParams' => ['fields' => ['name' => 'Changed']]])->response;
}

function revisionCompletionPayloads($response): array
{
    return array_map(static function (string $controllerClass) use ($response): array {
        $method = new ReflectionMethod($controllerClass, '_createSubmitJsonResponsePayload');

        return $method->invoke(new $controllerClass('revision-completion', Craft::$app), $response, 'submit');
    }, [SubmissionsController::class, ServerSubmissionsController::class]);
}

it('redirects a front-end edit to a redirect set in the template', function (string $method, bool $refresh): void {
    [$form, $submission] = revisionCompletionFixture();
    $template = $method === 'setRedirectUrl'
        ? fn(Form $form) => $form->setRedirectUrl('/manage?datum=2026-11-27')
        : fn(Form $form) => $form->setSettings(['redirectUrl' => '/manage?datum=2026-11-27']);
    $savedCompletion = Submission::find()->id($submission->id)->one()->getMetadata('completion');
    $resolved = 0;
    $observe = function () use (&$resolved): void { $resolved++; };
    Event::on(CompletionResolver::class, CompletionResolver::EVENT_RESOLVE_COMPLETION, $observe);
    Event::on(SubmissionsController::class, SubmissionsController::EVENT_AFTER_SUBMISSION_REQUEST, $observe);

    try {
        $response = revisionCompletionEdit($form, $submission, $template, $refresh);
    } finally {
        Event::off(CompletionResolver::class, CompletionResolver::EVENT_RESOLVE_COMPLETION, $observe);
        Event::off(SubmissionsController::class, SubmissionsController::EVENT_AFTER_SUBMISSION_REQUEST, $observe);
    }

    expect($response->success)->toBeTrue()
        ->and($response->outcome->type)->toBe(SubmissionOutcomeType::REVISED)
        ->and($response->outcome->data['completion']['behavior'] ?? null)->toBe('redirect')
        ->and($response->outcome->data['redirect']['url'] ?? null)->toBe('/manage?datum=2026-11-27')
        ->and($resolved)->toBe(0)
        ->and(Submission::find()->id($submission->id)->one()->getMetadata('completion'))->toBe($savedCompletion);

    foreach (revisionCompletionPayloads($response) as $payload) {
        expect($payload['redirectUrl'])->toBe('/manage?datum=2026-11-27')
            ->and($payload['redirect']['url'])->toBe('/manage?datum=2026-11-27');
    }
})->with(['setRedirectUrl', 'setSettings'])->with(['rendered token' => false, 'refreshed token' => true]);

it('ignores a redirect the original submission was made with when editing it', function (): void {
    [$form] = revisionCompletionFixture();
    $form->setRedirectUrl('/thanks-for-registering');
    $submission = formie()->submission($form)->with(['name' => 'Original'])->save();
    $response = revisionCompletionEdit($form, Submission::find()->id($submission->id)->one());

    expect($response->outcome->type)->toBe(SubmissionOutcomeType::REVISED)
        ->and($response->outcome->data)->not->toHaveKey('completion')
        ->and($response->outcome->data['revisionMessage'] ?? null)->toBe('Submission saved.');
});

it('shows a translatable revision message for a front-end edit without a template redirect', function (array $settings): void {
    [$form, $submission] = revisionCompletionFixture($settings);
    $response = revisionCompletionEdit($form, $submission);

    expect($response->success)->toBeTrue()
        ->and($response->outcome->type)->toBe(SubmissionOutcomeType::REVISED)
        ->and($response->outcome->data)->not->toHaveKey('completion')
        ->and($response->outcome->data['revisionMessage'] ?? null)->toBe('Submission saved.');

    foreach (revisionCompletionPayloads($response) as $payload) {
        expect($payload['completion'])->toBeNull()
            ->and($payload['redirect'])->toBeNull()
            ->and($payload['successMessage'])->toBe('Submission saved.');
    }
})->with([
    'message form' => [[]],
    // The form's own After Completion redirect is for new submissions only.
    'redirect form' => [['completionBehavior' => 'redirect', 'redirectUrl' => '/thanks']],
]);

it('keeps edits outside the front end free of completion', function (): void {
    [$form, $submission] = revisionCompletionFixture();
    $form->setSubmission($submission);
    $form->setRedirectUrl('/manage');
    $response = runSubmissionCommand(submissionCommand([
        'form' => $form,
        'submission' => $submission,
        'operation' => SubmissionOperation::REVISE,
    ]));

    expect($response->outcome->type)->toBe(SubmissionOutcomeType::REVISED)
        ->and($response->outcome->data)->not->toHaveKey('completion')
        ->and($response->outcome->data)->not->toHaveKey('revisionMessage');
});
