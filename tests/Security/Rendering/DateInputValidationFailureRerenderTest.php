<?php

declare(strict_types=1);

use craft\web\View;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Date;
use verbb\formie\helpers\ValidationMessagesHelper;

use Craft;

it('renders date text input sub-field error state for invalid calendar dates', function (): void {
    $field = new Date([
        'handle' => 'birthday',
        'displayType' => 'inputs',
    ]);
    $field->setRows((new Date(['displayType' => 'inputs']))->getSubFields());

    $dayField = $field->getFieldByHandle('day');

    $submission = new Submission();
    $submission->addError($dayField->valueKey(), $dayField->getValidationMessage(ValidationMessagesHelper::KEY_INVALID));

    $form = new Form([
        'title' => 'Date Input Failure Rerender',
        'handle' => 'dateInputFailureRerender',
    ]);
    $form->setCurrentSubmission($submission);

    $view = Craft::$app->getView();
    $oldTemplateMode = $view->getTemplateMode();
    $view->setTemplateMode(View::TEMPLATE_MODE_CP);

    try {
        $rendered = (string)$dayField->renderInput($form, '31');
    } finally {
        $view->setTemplateMode($oldTemplateMode);
    }

    expect($rendered)
        ->toContain('formie-input-error')
        ->toContain('aria-invalid="true"')
        ->toContain('data-formie-input-error-state');
})->group('security');
