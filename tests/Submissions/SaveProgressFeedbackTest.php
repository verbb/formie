<?php

declare(strict_types=1);

use verbb\formie\controllers\SubmissionsController;
use verbb\formie\controllers\server\SubmissionsController as ServerSubmissionsController;
use verbb\formie\elements\Submission;
use verbb\formie\enums\NavigationIntent;
use verbb\formie\enums\SubmissionOperation;
use Tests\Support\WebRequestTestHelper;

it('reports saved progress without claiming the incomplete submission is complete', function (string $controllerClass): void {
    $form = formie()->form()->singleLineTextField('name', ['required' => true])->create();
    $form->settings->setAttributes(['successMessage' => 'Your booking is confirmed.'], false);
    $submission = new Submission();
    $submission->setForm($form);
    $response = runSubmissionCommand(submissionCommand([
        'form' => $form,
        'submission' => $submission,
        'operation' => SubmissionOperation::SAVE_DRAFT,
        'navigation' => NavigationIntent::STAY,
    ]));

    $controller = new $controllerClass('save-progress-feedback', Craft::$app);
    $method = new ReflectionMethod($controllerClass, '_createSubmitJsonResponsePayload');
    $payload = $method->invoke($controller, $response, 'save');

    expect($response->success)->toBeTrue()
        ->and($response->submission->isIncomplete)->toBeTrue()
        ->and($payload['isFinalPage'])->toBeFalse()
        ->and($payload['completion'])->toBeNull()
        ->and($payload['successMessage'])->toBe('Your progress has been saved.');
})->with([SubmissionsController::class, ServerSubmissionsController::class]);

it('preserves the form page when a resumed page sends an origin-only referrer', function (string $controllerClass): void {
    $form = formie()->form()->singleLineTextField('name')->create();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form, $controllerClass): void {
        $origin = preg_replace('#^(https?://[^/]+).*#', '$1/', \craft\helpers\UrlHelper::siteUrl());
        $request->getHeaders()->set('Referer', $origin);
        $submission = new Submission();
        $submission->setForm($form);
        $response = runSubmissionCommand(submissionCommand([
            'form' => $form,
            'submission' => $submission,
            'operation' => SubmissionOperation::SAVE_DRAFT,
            'navigation' => NavigationIntent::STAY,
        ]));
        $controller = new $controllerClass('resaved-progress-url', Craft::$app);
        $method = new ReflectionMethod($controllerClass, '_createSaveResumePayload');
        $payload = $method->invoke($controller, $form, $response->submission);
        parse_str(parse_url($payload['resumeUrl'], PHP_URL_QUERY), $query);

        expect(parse_url($payload['resumeUrl'], PHP_URL_PATH))->toBe(parse_url(\craft\helpers\UrlHelper::siteUrl('workshop?handle=conference'), PHP_URL_PATH))
            ->and($query['handle'] ?? null)->toBe('conference');
    }, ['method' => 'POST', 'requestUri' => '/workshop?handle=conference', 'queryParams' => ['handle' => 'conference']]);
})->with([SubmissionsController::class, ServerSubmissionsController::class]);

it('preserves the form page query when saving without a referrer', function (string $controllerClass): void {
    $form = formie()->form()->singleLineTextField('name')->create();

    WebRequestTestHelper::withWebRequestContext(function () use ($form, $controllerClass): void {
        $submission = new Submission();
        $submission->setForm($form);
        $response = runSubmissionCommand(submissionCommand([
            'form' => $form,
            'submission' => $submission,
            'operation' => SubmissionOperation::SAVE_DRAFT,
            'navigation' => NavigationIntent::STAY,
        ]));
        $controller = new $controllerClass('save-progress-url', Craft::$app);
        $method = new ReflectionMethod($controllerClass, '_createSaveResumePayload');
        $payload = $method->invoke($controller, $form, $response->submission);
        parse_str(parse_url($payload['resumeUrl'], PHP_URL_QUERY), $query);

        expect($query['handle'] ?? null)->toBe('conference')
            ->and($query['resumeToken'] ?? null)->toBe($payload['resumeToken']);
    }, ['method' => 'POST', 'requestUri' => '/workshop?handle=conference', 'queryParams' => ['handle' => 'conference']]);
})->with([SubmissionsController::class, ServerSubmissionsController::class]);
