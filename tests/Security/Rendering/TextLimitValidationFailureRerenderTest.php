<?php

declare(strict_types=1);

use craft\web\View;
use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\models\SubmissionCommand;
use verbb\formie\services\SubmissionWorkflow;

it('renders text limit field and form errors after a page-reload validation failure', function (): void {
    $form = formie()
        ->form(['title' => 'Text Limit Failure Rerender'])
        ->settings(['disableCaptchas' => true])
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

    $response = runSubmissionCommand(submissionCommand([
        'operation' => \verbb\formie\enums\SubmissionOperation::SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'navigation' => \verbb\formie\enums\NavigationIntent::ADVANCE,
    ]));

    expect($response->success)->toBeFalse()
        ->and($response->submission?->getErrors('message'))->not->toBeEmpty();

    $failedSubmission = $response->submission;
    $failedSubmission->addError('form', $form->settings->getErrorMessage());
    $form->setCurrentSubmission($failedSubmission);

    Tests\Support\WebRequestTestHelper::withWebRequestContext(function () use ($form): void {
        $view = Craft::$app->getView();
        $oldTemplateMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_SITE);

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
    });
})->group('security');

it('renders text limit input error state for failed page-reload submissions', function (): void {
    $form = formie()
        ->form(['title' => 'Text Limit Input Rerender'])
        ->settings(['disableCaptchas' => true])
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

    $response = runSubmissionCommand(submissionCommand([
        'operation' => \verbb\formie\enums\SubmissionOperation::SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'navigation' => \verbb\formie\enums\NavigationIntent::ADVANCE,
    ]));

    $form->setCurrentSubmission($response->submission);
    $field = $form->getFieldByHandle('message');
    $rendered = (string)$field?->renderInput($form, $response->submission?->getFieldValue('message'));

    expect($response->success)->toBeFalse()
        ->and($rendered)->toContain('data-formie-max-chars="5"')
        ->and($rendered)->toContain('formie-input-error')
        ->and($rendered)->toContain('aria-invalid="true"');
})->group('security');
