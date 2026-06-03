<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use verbb\formie\content\SubmissionContentNormalizer;

it('persists option field values for dropdown radio and checkboxes', function (): void {
    $options = [
        ['label' => 'One', 'value' => 'one'],
        ['label' => 'Two', 'value' => 'two'],
    ];

    $form = formie()
        ->form(['title' => 'Options Fields'])
        ->dropdownField('choice', ['options' => $options])
        ->radioField('priority', ['options' => $options])
        ->checkboxesField('topics', ['options' => $options])
        ->create();

    $submission = formie()
        ->submission($form)
        ->with([
            'choice' => 'one',
            'priority' => 'two',
            'topics' => ['one', 'two'],
        ])
        ->save();

    $choiceResults = Submission::find()
        ->formId($form->id)
        ->field('choice', 'one')
        ->all();

    $priorityResults = Submission::find()
        ->formId($form->id)
        ->field('priority', 'two')
        ->all();

    $topicsResults = Submission::find()
        ->formId($form->id)
        ->field('topics', 'two')
        ->all();

    $choiceString = $submission->getFieldValueAsString('choice');
    $choiceSummary = $submission->getFieldValueForSummary('choice');
    $priorityString = $submission->getFieldValueAsString('priority');
    $prioritySummary = $submission->getFieldValueForSummary('priority');

    expect($submission->id)->not->toBeNull()
        ->and([$choiceString, $choiceSummary])->toContain('one')
        ->and([$choiceString, $choiceSummary])->toContain('One')
        ->and([$priorityString, $prioritySummary])->toContain('two')
        ->and([$priorityString, $prioritySummary])->toContain('Two')
        ->and($choiceResults)->toHaveCount(1)
        ->and($priorityResults)->toHaveCount(1)
        ->and($topicsResults)->not->toBeEmpty()
        ->and((int)$choiceResults[0]->id)->toBe((int)$submission->id)
        ->and((int)$priorityResults[0]->id)->toBe((int)$submission->id);
});

it('does not restore checkbox defaults when a submitted checkbox group is explicitly unchecked', function (): void {
    $options = [
        ['label' => 'One', 'value' => 'one', 'default' => true],
    ];

    $form = formie()
        ->form(['title' => 'Checkbox Defaults ' . uniqid()])
        ->singleLineTextField('fullName')
        ->checkboxesField('topics', ['options' => $options])
        ->create();

    $submission = new Submission();
    $submission->setForm($form);

    (new SubmissionContentNormalizer())->normalizeRequestPayload($submission, [
        'fullName' => 'Unchecked User',
    ], 'fields');

    expect($submission->getFieldValueAsArray('topics'))->toBe([])
        ->and(json_encode($submission->serializeFieldValues()))->not->toContain('one');
});
