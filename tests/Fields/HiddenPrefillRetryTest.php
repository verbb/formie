<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use verbb\formie\models\FormInstanceConfig;

it('preserves posted hidden values when rendering a failed submission with a query prefill', function (string $value): void {
    $form = formie()->form()->hiddenField('campaign', ['defaultValue' => 'direct'])->create();
    $field = $form->getFieldByHandle('campaign');
    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValue('campaign', $value);
    $form->setSubmission($submission);
    $form->replaceInstanceConfig(new FormInstanceConfig(prefill: [$field->uid => 'original-campaign']));

    expect($field->getInputTemplateVariables($form, $value)['value'])->toBe($value);
})->with(['changed-campaign', '']);

it('uses a hidden query prefill when no rendered value has been supplied', function (): void {
    $form = formie()->form()->hiddenField('campaign', ['defaultValue' => 'direct'])->create();
    $field = $form->getFieldByHandle('campaign');
    $form->markInstanceEstablished();
    $form->replaceInstanceConfig(new FormInstanceConfig(prefill: [$field->uid => 'original-campaign']));

    expect($field->getInputTemplateVariables($form, null)['value'])->toBe('original-campaign');
});
