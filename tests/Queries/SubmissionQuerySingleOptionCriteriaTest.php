<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;

it('queries single-option fields by value and label via dynamic handle calls', function (): void {
    $options = [
        ['label' => 'Option 1', 'value' => 'option1'],
        ['label' => 'Option 2', 'value' => 'option2'],
    ];

    $form = formie()
        ->form(['title' => 'Single Option Query Criteria'])
        ->dropdownField('dropdown', ['options' => $options])
        ->radioField('radio', ['options' => $options])
        ->create();

    $matchingSubmission = formie()->submission($form)->with([
        'dropdown' => 'option2',
        'radio' => 'option2',
    ])->save();

    formie()->submission($form)->with([
        'dropdown' => 'option1',
        'radio' => 'option1',
    ])->save();

    $assertDynamicResults = function(string $handle, mixed $criteria) use ($form, $matchingSubmission): void {
        $results = Submission::find()
            ->formId($form->id)
            ->{$handle}($criteria)
            ->all();

        expect($results)->toHaveCount(1);
        expect($results[0]->id)->toBe($matchingSubmission->id);
    };

    // Default criteria behavior remains value-based.
    $assertDynamicResults('dropdown', 'option2');
    $assertDynamicResults('dropdown', ['value' => 'option2']);

    // Single-option fields can now resolve labels to their stored values.
    $assertDynamicResults('dropdown', ['label' => 'Option 2']);
    $assertDynamicResults('radio', ['label' => 'Option 2']);

    $missingLabelResults = Submission::find()
        ->formId($form->id)
        ->dropdown(['label' => 'Missing Option'])
        ->all();

    expect($missingLabelResults)->toHaveCount(0);
});
