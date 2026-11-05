<?php

use verbb\formie\Formie;
use verbb\formie\conditions\ConditionVisibility;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Name;
use verbb\formie\services\RuntimeConfiguration;

function singleNameRuntimeForm(bool $multiple = false): Form
{
    $form = formie()->form()
        ->settings(['disableCaptchas' => true])
        ->nameField('fullName', [
            'label' => 'Full name',
            'required' => true,
            'useMultipleFields' => true,
            'rows' => (new Name(['useMultipleFields' => true]))->getSubFields(),
        ])
        ->singleLineTextField('other', ['required' => true])
        ->create();
    $field = $form->getFieldByHandle('fullName');
    $layoutId = $field->nestedLayoutId;
    $field->useMultipleFields = $multiple;
    expect(Formie::$plugin->getFields()->saveField($field))->toBeTrue();

    // Reload the retained layout, just as a request after changing the builder mode would.
    Formie::$plugin->getForms()->invalidateFormCaches();
    $form = Form::find()->id($form->id)->one();
    $field = $form->getFieldByHandle('fullName');
    expect($field->hasFieldLayout())->toBe($multiple)
        ->and($field->nestedLayoutId)->toBe($layoutId)
        ->and($field->getFields())->not->toBeEmpty();

    return $form;
}

it('preserves single-line Name answers through submission with a retained child layout', function (string $name, string $other, bool $success): void {
    $form = singleNameRuntimeForm();
    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValueFromRequest('fullName', $name);
    $submission->setFieldValueFromRequest('other', $other);

    $response = runSubmissionCommand(submissionCommand([
        'form' => $form,
        'submission' => $submission,
    ]));

    expect($response->success)->toBe($success)
        ->and((string)$response->submission->getFieldValue('fullName'))->toBe($name);

    if ($success) {
        $saved = Submission::find()->id($response->submission->id)->status(null)->one();
        expect($saved)->not->toBeNull()
            ->and((string)$saved->getFieldValue('fullName'))->toBe($name);
    } else {
        expect($response->submission->hasErrors('fullName'))->toBe($name === '');
        $form->setCurrentSubmission($response->submission);
        $html = (string)Formie::$plugin->getRendering()->renderField($form, 'fullName');
        $document = new DOMDocument();
        @$document->loadHTML($html);
        $input = (new DOMXPath($document))->query('//input[@name="fields[fullName]"]')->item(0);
        expect($input)->not->toBeNull()
            ->and($input->getAttribute('value'))->toBe($name);
    }
})->with([
    'valid answer saves' => ['Ada Lovelace', 'present', true],
    'another field fails and Name rerenders' => ['Ada Lovelace', '', false],
    'empty Name still fails required validation' => ['', 'present', false],
]);

it('preserves initial single-line Name values with a retained child layout', function (): void {
    $form = singleNameRuntimeForm();
    Formie::$plugin->getRendering()->populateFormValues($form, ['fullName' => 'Ada Lovelace']);
    $submission = new Submission();
    $submission->setForm($form);

    (new RuntimeConfiguration())->applyValues($submission);

    expect((string)$submission->getFieldValue('fullName'))->toBe('Ada Lovelace');
});

it('does not clear inactive Name children from a single-line answer', function (): void {
    $form = singleNameRuntimeForm();
    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValueFromRequest('fullName', 'Ada Lovelace');

    (new RuntimeConfiguration())->applyValues($submission);
    expect((string)$submission->getFieldValue('fullName'))->toBe('Ada Lovelace');

    (new ConditionVisibility())->clear($submission);
    expect((string)$submission->getFieldValue('fullName'))->toBe('Ada Lovelace');
});

it('still resolves active multi-part Name children and saves their answers', function (): void {
    $form = singleNameRuntimeForm(true);
    Formie::$plugin->getRendering()->populateFormValues($form, ['fullName.lastName' => 'Lovelace'], true);
    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValueFromRequest('fullName', ['firstName' => 'Ada']);
    $submission->setFieldValueFromRequest('other', 'present');

    $response = runSubmissionCommand(submissionCommand([
        'form' => $form,
        'submission' => $submission,
    ]));

    expect($response->success)->toBeTrue();
    $saved = Submission::find()->id($response->submission->id)->status(null)->one();
    expect($saved->getFieldValue('fullName.firstName'))->toBe('Ada')
        ->and($saved->getFieldValue('fullName.lastName'))->toBe('Lovelace');
});
