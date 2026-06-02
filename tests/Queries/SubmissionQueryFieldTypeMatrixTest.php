<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;

it('queries representative field families by their values', function (): void {
    $options = [
        ['label' => 'One', 'value' => 'one'],
        ['label' => 'Two', 'value' => 'two'],
    ];

    $form = formie()
        ->form(['title' => 'Field Type Query Matrix'])
        ->singleLineTextField('fullName')
        ->emailField('email')
        ->numberField('age')
        ->dropdownField('choice', ['options' => $options])
        ->radioField('priority', ['options' => $options])
        ->checkboxesField('topics', ['options' => $options])
        ->create();

    $matchingSubmission = formie()->submission($form)->with([
        'fullName' => 'Query Matrix User',
        'email' => 'query-matrix@example.test',
        'age' => '42',
        'choice' => 'one',
        'priority' => 'two',
        'topics' => ['one', 'two'],
    ])->save();

    formie()->submission($form)->with([
        'fullName' => 'Other User',
        'email' => 'other@example.test',
        'age' => '99',
        'choice' => 'two',
        'priority' => 'one',
        'topics' => ['one'],
    ])->save();

    $assertResults = function(string $handle, mixed $value) use ($form, $matchingSubmission): void {
        $results = Submission::find()
            ->formId($form->id)
            ->field($handle, $value)
            ->all();

        expect($results)->toHaveCount(1);
        expect($results[0]->id)->toBe($matchingSubmission->id);
    };

    $assertResults('fullName', 'Query Matrix User');
    $assertResults('email', 'query-matrix@example.test');
    $assertResults('age', '42');
    $assertResults('choice', 'one');
    $assertResults('priority', 'two');
});
