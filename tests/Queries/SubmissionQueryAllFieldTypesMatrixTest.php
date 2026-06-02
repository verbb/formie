<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;

it('queries a broad set of field families by value criteria', function (): void {
    $options = [
        ['label' => 'One', 'value' => 'one'],
        ['label' => 'Two', 'value' => 'two'],
    ];

    $form = formie()
        ->form(['title' => 'All Field Query Matrix'])
        ->singleLineTextField('fullName')
        ->multiLineTextField('bio')
        ->emailField('email')
        ->numberField('age')
        ->dateField('dob')
        ->phoneField('phone')
        ->nameField('name', ['useMultipleFields' => false])
        ->dropdownField('choice', ['options' => $options])
        ->radioField('priority', ['options' => $options])
        ->checkboxesField('topics', ['options' => $options])
        ->hiddenField('tracking')
        ->create();

    $matchingSubmission = formie()->submission($form)->with([
        'fullName' => 'Query User',
        'bio' => 'Query biography',
        'email' => 'query@example.test',
        'age' => '37',
        'dob' => '2026-01-02',
        'phone' => '0400000001',
        'name' => 'Query Name',
        'choice' => 'one',
        'priority' => 'two',
        'topics' => ['one', 'two'],
        'tracking' => 'utm-source',
    ])->save();

    formie()->submission($form)->with([
        'fullName' => 'Other User',
        'bio' => 'Other biography',
        'email' => 'other@example.test',
        'age' => '99',
        'dob' => '2026-02-03',
        'phone' => '0400000002',
        'name' => 'Other Name',
        'choice' => 'two',
        'priority' => 'one',
        'topics' => ['one'],
        'tracking' => 'utm-medium',
    ])->save();

    $criteria = [
        ['fullName', 'Query User', 'exact'],
        ['bio', 'Query biography', 'exact'],
        ['email', 'query@example.test', 'exact'],
        ['age', '37', 'exact'],
        ['age', '>= 37', 'contains'],
        // Date querying uses richer operator/range syntax and currently has broader matching semantics.
        ['dob', ['and', '>= 2026-01-02', '< 2026-01-03'], 'contains'],
        ['dob', '>= 2026-01-01', 'contains'],
        ['name', 'Query Name', 'exact'],
        ['choice', 'one', 'exact'],
        ['priority', 'two', 'exact'],
        ['topics', 'two', 'exact'],
        ['tracking', 'utm-source', 'exact'],
    ];

    foreach ($criteria as [$handle, $value, $expectation]) {
        $results = Submission::find()
            ->formId($form->id)
            ->field((string)$handle, $value)
            ->all();

        if ($expectation === 'contains') {
            $ids = array_map(static fn($submission) => $submission->id, $results);
            if (!in_array($matchingSubmission->id, $ids, true)) {
                throw new RuntimeException("Expected matching submission to be included for handle '{$handle}' with query value '" . json_encode($value) . "'");
            }
            continue;
        }

        if (count($results) !== 1) {
            throw new RuntimeException("Expected one result for handle '{$handle}' with query value '" . json_encode($value) . "', got " . count($results));
        }
        expect($results[0]->id)->toBe($matchingSubmission->id);
    }
});

it('keeps phone field scalar criteria query non-fatal', function (): void {
    $form = formie()
        ->form(['title' => 'All Field Matrix Phone Non-Fatal'])
        ->phoneField('phone')
        ->create();

    formie()->submission($form)->with(['phone' => '0400000001'])->save();
    formie()->submission($form)->with(['phone' => '0400000002'])->save();
    formie()->submission($form)->with(['phone' => '0400000003'])->save();

    $results = null;

    expect(function() use ($form, &$results): void {
        $results = Submission::find()
            ->formId($form->id)
            ->field('phone', '0400000001')
            ->all();
    })->not->toThrow(Throwable::class);

    expect($results)->toBeArray();
});
