<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use verbb\formie\models\SubmissionCommand;
use verbb\formie\services\SubmissionWorkflow;

dataset('edit_submit_methods', ['ajax', 'page-reload']);

it('keeps edit-existing workflow persistence stable under each submit-method setting', function (string $submitMethod): void {
    $form = formie()
        ->form(['title' => 'Edit Transport ' . $submitMethod])
        ->singleLineTextField('fullName', ['required' => true])
        ->create();

    $form->settings->setAttributes(['submitMethod' => $submitMethod], false);
    expect(Craft::$app->elements->saveElement($form))->toBeTrue();

    $existing = formie()
        ->submission($form)
        ->with(['fullName' => 'Before Edit'])
        ->save();

    $existing->setFieldValueFromRequest('fullName', 'After Edit');

    $response = runSubmissionCommand(submissionCommand([
        'operation' => \verbb\formie\enums\SubmissionOperation::REVISE,
        'form' => $form,
        'submission' => $existing,
        'navigation' => \verbb\formie\enums\NavigationIntent::ADVANCE,
    ]));

    $reloaded = Submission::find()->id($existing->id)->status(null)->one();

    expect($response->success)->toBeTrue()
        ->and($reloaded)->not->toBeNull()
        ->and($reloaded->getFieldValue('fullName'))->toBe('After Edit');
})->with('edit_submit_methods');

it('keeps edit-existing validation behavior stable across submit methods', function (string $submitMethod): void {
    $form = formie()
        ->form(['title' => 'Edit Validation Transport ' . $submitMethod])
        ->singleLineTextField('fullName', ['required' => true])
        ->create();

    $form->settings->setAttributes(['submitMethod' => $submitMethod], false);
    expect(Craft::$app->elements->saveElement($form))->toBeTrue();

    $existing = formie()
        ->submission($form)
        ->with(['fullName' => 'Before Edit'])
        ->save();

    $existing->setFieldValueFromRequest('fullName', '');

    $response = runSubmissionCommand(submissionCommand([
        'operation' => \verbb\formie\enums\SubmissionOperation::REVISE,
        'form' => $form,
        'submission' => $existing,
        'navigation' => \verbb\formie\enums\NavigationIntent::ADVANCE,
    ]));

    expect($response->success)->toBeFalse()
        ->and($response->submission)->toHaveFieldError('fullName');
})->with('edit_submit_methods');
