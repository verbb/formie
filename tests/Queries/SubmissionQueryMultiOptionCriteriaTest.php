<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;

it('queries checkboxes by scalar value and explicit value/label criteria via dynamic handle calls', function (): void {
    $options = [
        ['label' => 'Option 1', 'value' => 'option1'],
        ['label' => 'Option 2', 'value' => 'option2'],
        ['label' => 'Option 3', 'value' => 'option3'],
    ];

    $form = formie()
        ->form(['title' => 'Multi Option Query Criteria'])
        ->checkboxesField('checkboxes', ['options' => $options])
        ->create();

    $matchingSubmission = formie()->submission($form)->with([
        'checkboxes' => ['option2', 'option3'],
    ])->save();

    formie()->submission($form)->with([
        'checkboxes' => ['option1'],
    ])->save();

    $assertDynamicResults = function(mixed $criteria) use ($form, $matchingSubmission): void {
        $results = Submission::find()
            ->formId($form->id)
            ->checkboxes($criteria)
            ->all();

        expect($results)->toHaveCount(1);
        expect($results[0]->id)->toBe($matchingSubmission->id);
    };

    // Default criteria behavior remains value-based.
    $assertDynamicResults('option2');
    $assertDynamicResults(['value' => 'option2']);
    $assertDynamicResults(['value' => ['option2', 'option3']]);

    // Label criteria resolves to option values.
    $assertDynamicResults(['label' => 'Option 2']);
    $assertDynamicResults(['label' => ['Option 2', 'Option 3']]);

    $missingLabelResults = Submission::find()
        ->formId($form->id)
        ->checkboxes(['label' => 'Missing Option'])
        ->all();

    expect($missingLabelResults)->toHaveCount(0);
});
