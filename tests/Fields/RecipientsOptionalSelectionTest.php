<?php

declare(strict_types=1);

use craft\base\Element;
use verbb\formie\elements\Submission;

function recipientsOptionalSelectionForm(string $displayType, bool $required = false): mixed
{
    $options = [
        ['label' => 'Sales', 'value' => 'sales@example.test'],
        ['label' => 'Support', 'value' => 'support@example.test'],
    ];

    if ($displayType === 'dropdown') {
        array_unshift($options, ['label' => 'Choose a team', 'value' => '']);
    }

    return formie()
        ->form(['title' => 'Recipients Optional Selection ' . uniqid()])
        ->recipientsField('team', ['displayType' => $displayType, 'required' => $required, 'options' => $options])
        ->create();
}

// Front-end submissions validate in the live scenario, where required rules apply.
function recipientsOptionalSelectionSubmission(mixed $form, ?array $values = null): Submission
{
    $submission = new Submission();
    $submission->setForm($form);
    $submission->title = 'Recipients Optional Selection';
    $submission->setScenario(Element::SCENARIO_LIVE);

    foreach ($values ?? [] as $handle => $value) {
        $submission->setFieldValueFromRequest($handle, $value);
    }

    Craft::$app->getElements()->saveElement($submission);

    return $submission;
}

it('accepts an optional Recipients field left empty', function (string $displayType, ?array $values): void {
    $submission = recipientsOptionalSelectionSubmission(recipientsOptionalSelectionForm($displayType), $values);

    expect($submission->getErrors('team'))->toBe([])
        ->and($submission->id)->not->toBeNull()
        ->and($submission->getFieldValue('team')->value ?? null)->toBeNull();
})->with([
    'radio with nothing posted' => ['radio', null],
    'radio with an empty string' => ['radio', ['team' => '']],
    'dropdown on its empty option' => ['dropdown', ['team' => '']],
]);

it('still rejects unknown Recipients selections', function (string $displayType, string $value): void {
    $submission = recipientsOptionalSelectionSubmission(recipientsOptionalSelectionForm($displayType), ['team' => $value]);

    expect($submission->getErrors('team'))->toBe(['Select a valid recipient.'])
        ->and($submission->id)->toBeNull();
})->with([
    'radio with an unlisted address' => ['radio', 'intruder@example.test'],
    'radio with a forged token' => ['radio', 'recipient:v1:' . str_repeat('0', 64)],
    'dropdown with an unlisted address' => ['dropdown', 'intruder@example.test'],
]);

it('still requires a selection when the Recipients field is required', function (string $displayType): void {
    $submission = recipientsOptionalSelectionSubmission(recipientsOptionalSelectionForm($displayType, true), ['team' => '']);

    expect($submission->getErrors('team'))->not->toBe([])
        ->and($submission->getErrors('team'))->not->toContain('Select a valid recipient.')
        ->and($submission->id)->toBeNull();
})->with(['radio', 'dropdown']);

it('saves a listed Recipients selection', function (string $displayType): void {
    $submission = recipientsOptionalSelectionSubmission(recipientsOptionalSelectionForm($displayType), ['team' => 'support@example.test']);

    expect($submission->getErrors('team'))->toBe([])
        ->and($submission->getFieldValue('team')->value)->toBe('support@example.test');
})->with(['radio', 'dropdown']);
