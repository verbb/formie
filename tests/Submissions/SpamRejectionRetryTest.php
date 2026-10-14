<?php

use craft\web\View;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Submission;
use verbb\formie\controllers\SubmissionsController;
use verbb\formie\controllers\server\SubmissionsController as ServerSubmissionsController;
use verbb\formie\models\SubmissionResponse;

it('renders rejected values without continuing a terminal spam record', function (bool $spam, bool $incomplete, bool $continues): void {
    $form = formie()->form()->singleLineTextField('message')->create();
    $submission = new Submission();
    $submission->setForm($form);
    $submission->uid = Craft::$app->getSecurity()->generateRandomString();
    $submission->isSpam = $spam;
    $submission->isIncomplete = $incomplete;
    $submission->stateVersion = 3;
    $submission->setFieldValue('message', 'Please preserve this enquiry');
    $form->setCurrentSubmission($submission);

    foreach ([SubmissionsController::class, ServerSubmissionsController::class] as $controllerClass) {
        $response = new SubmissionResponse(['form' => $form, 'submission' => $submission, 'success' => false]);
        $controller = new $controllerClass('spam-retry', Craft::$app);
        $method = new ReflectionMethod($controllerClass, '_createSubmitJsonResponsePayload');
        $payload = $method->invoke($controller, $response, 'submit');
        expect($payload['resetSubmissionIdentity'])->toBe(!$continues);
    }

    WebRequestTestHelper::withWebRequestContext(function () use ($form, $submission, $continues): void {
        $view = Craft::$app->getView();
        $mode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_CP);

        try {
            $html = $view->renderTemplate('formie/_special/form-template/form/hidden-inputs', [
                'form' => $form,
                'submission' => $submission,
                'csrfInputOptions' => null,
                'uploadCreateToken' => '',
                'currentPage' => null,
            ]);
        } finally {
            $view->setTemplateMode($mode);
        }

        expect(str_contains($html, 'name="submissionUid"'))->toBe($continues)
            ->and($html)->toContain('name="expectedVersion" value="' . ($continues ? '3' : '0') . '"')
            ->and((string)$submission->getFieldValue('message'))->toBe('Please preserve this enquiry');
    });
})->with([
    'terminal spam' => [true, false, false],
    'ordinary draft' => [false, true, true],
    'incomplete spam' => [true, true, true],
]);
