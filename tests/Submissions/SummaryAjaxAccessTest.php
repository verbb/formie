<?php

declare(strict_types=1);

use verbb\formie\controllers\SubmissionsController;
use verbb\formie\controllers\server\SubmissionsController as ServerSubmissionsController;
use verbb\formie\elements\Submission;
use verbb\formie\enums\NavigationIntent;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\helpers\FieldAccess;

it('issues summary access after an AJAX page save', function (string $controllerClass): void {
    $form = formie()->form()->singleLineTextField('answer')->onPage(2)->summaryField('review')->create();
    $submission = new Submission();
    $submission->setForm($form);
    $response = runSubmissionCommand(submissionCommand([
        'form' => $form, 'submission' => $submission,
        'operation' => SubmissionOperation::SAVE_DRAFT, 'navigation' => NavigationIntent::STAY,
    ]));
    $controller = new $controllerClass('summary-access', Craft::$app);
    $method = new ReflectionMethod($controllerClass, '_createSubmitJsonResponsePayload');
    $payload = $method->invoke($controller, $response, 'save');
    $field = $form->getFieldByHandle('review');
    $token = $payload['summaryTokens'][$field->uid] ?? null;
    expect($token)->toBeString();
    $context = FieldAccess::resolveAccessToken($token);
    expect($context['submissionUid'])->toBe($response->submission->uid)
        ->and($context['fieldId'])->toBe((int)$field->id);
})->with([SubmissionsController::class, ServerSubmissionsController::class]);


it('does not issue summary access for rejected, spam or simulated submissions', function (string $reason): void {
    $form = formie()->form()->singleLineTextField('answer')->summaryField('review')->create();
    $submission = formie()->submission($form)->with(['answer' => 'Saved answer'])->save();
    $response = new \verbb\formie\models\SubmissionResponse([
        'form' => $form, 'submission' => $submission, 'success' => true,
    ]);
    if ($reason === 'rejected') {
        $response->success = false;
    } elseif ($reason === 'spam') {
        $submission->isSpam = true;
    } elseif ($reason === 'unsaved') {
        $submission->id = null;
    } else {
        $response->outcome = new \verbb\formie\models\SubmissionOutcome(
            \verbb\formie\enums\SubmissionOutcomeType::REJECTED,
            data: ['fakeSuccess' => true],
        );
    }
    expect($response->getSummaryTokens())->toBe([]);
})->with(['rejected', 'spam', 'unsaved', 'fake success']);
