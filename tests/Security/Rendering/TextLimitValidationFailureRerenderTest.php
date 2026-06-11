<?php

declare(strict_types=1);

use craft\web\View;
use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\services\SubmissionWorkflow;

it('renders text limit field and form errors after a page-reload validation failure', function (): void {
    $form = formie()
        ->form(['title' => 'Text Limit Failure Rerender'])
        ->singleLineTextField('message', [
            'limit' => true,
            'max' => 10,
            'maxType' => 'characters',
        ])
        ->submitAction('message', ['method' => 'page-reload'])
        ->create();

    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValueFromRequest('message', str_repeat('a', 11));

    $response = (new SubmissionWorkflow())->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
    ]));

    expect($response->success)->toBeFalse()
        ->and($response->submission?->getErrors('message'))->not->toBeEmpty();

    $failedSubmission = $response->submission;
    $failedSubmission->addError('form', $form->settings->getErrorMessage());
    $form->setCurrentSubmission($failedSubmission);

    $view = Craft::$app->getView();
    $oldTemplateMode = $view->getTemplateMode();
    $view->setTemplateMode(View::TEMPLATE_MODE_CP);

    try {
        $html = (string)Formie::$plugin->getRendering()->renderForm($form);
    } finally {
        $view->setTemplateMode($oldTemplateMode);
    }

    expect($html)
        ->toContain('formie-field-has-error')
        ->toContain('formie-input-error')
        ->toContain('data-formie-field-error')
        ->toContain('data-formie-max-chars="10"')
        ->toContain('aria-invalid="true"')
        ->toContain($form->settings->getErrorMessage());
})->group('security');

it('renders text limit input error state for failed page-reload submissions', function (): void {
    $form = formie()
        ->form(['title' => 'Text Limit Input Rerender'])
        ->singleLineTextField('message', [
            'limit' => true,
            'max' => 5,
            'maxType' => 'characters',
        ])
        ->submitAction('message', ['method' => 'page-reload'])
        ->create();

    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValueFromRequest('message', 'abcdef');

    $response = (new SubmissionWorkflow())->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
    ]));

    $form->setCurrentSubmission($response->submission);
    $field = $form->getFieldByHandle('message');
    $rendered = (string)$field?->renderInput($form, $response->submission?->getFieldValue('message'));

    expect($response->success)->toBeFalse()
        ->and($rendered)->toContain('data-formie-max-chars="5"')
        ->and($rendered)->toContain('formie-input-error')
        ->and($rendered)->toContain('aria-invalid="true"');
})->group('security');
