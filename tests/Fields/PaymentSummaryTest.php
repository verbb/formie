<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Payment;
use verbb\formie\fields\SingleLineText;

it('leaves payment fields out of submission summaries', function (): void {
    $form = formie()->form()
        ->singleLineTextField('name')
        ->paymentField('payment')
        ->create();
    $submission = formie()->submission($form)->with(['name' => 'Jane'])->save();
    $submission->setFieldValue('payment', ['stripePaymentId' => 'pm_123']);
    expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();

    $loaded = Submission::find()->id($submission->id)->one();
    $handles = array_map(static fn(array $item) => $item['field']->handle, $loaded->getValuesForSummary());

    expect($handles)->toBe(['name'])
        ->and(json_encode(array_map(static fn(array $item) => (string)$item['text'], $loaded->getValuesForSummary())))->not->toContain('pm_123');
});

it('keeps provider IDs out of a Group summary', function (): void {
    $form = formie()->form()->groupField('wrap', ['rows' => [['fields' => [
        ['type' => SingleLineText::class, 'handle' => 'name', 'label' => 'Name'],
        ['type' => Payment::class, 'handle' => 'payment', 'label' => 'Card'],
    ]]]])->create();
    $form = Form::find()->id($form->id)->one();
    $field = $form->getFieldByHandle('wrap');
    $submission = new Submission();
    $submission->setForm($form);

    $summary = (string)$field->getValueForSummary(['name' => 'Jane', 'payment' => ['stripePaymentId' => 'pm_123']], $submission);

    expect($summary)->not->toContain('pm_123')
        ->and($summary)->toContain('<strong>Name</strong> Jane')
        ->and($summary)->not->toContain('Card');
});

it('leaves payment fields out of a Repeater summary', function (): void {
    $form = formie()->form()->repeaterField('rows', ['rows' => [['fields' => [
        ['type' => SingleLineText::class, 'handle' => 'name', 'label' => 'Name'],
        ['type' => Payment::class, 'handle' => 'payment', 'label' => 'Card'],
    ]]]])->create();
    $form = Form::find()->id($form->id)->one();
    $field = $form->getFieldByHandle('rows');
    $submission = new Submission();
    $submission->setForm($form);
    $value = $field->normalizeValue([['name' => 'Jane', 'payment' => ['stripePaymentId' => 'pm_123']]], $submission);
    $summary = (string)$field->getValueForSummary($value, $submission);

    expect($summary)->not->toContain('pm_123')
        ->and($summary)->toContain('<strong>Name</strong> Jane')
        ->and($summary)->not->toContain('Card');
});
