<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use verbb\formie\fields\subfields\NameFirst;
use verbb\formie\fields\subfields\NameLast;

it('queries name fields in single-value and child-object permutations', function (): void {
    $nameRows = [[
        'fields' => [
            [
                'type' => NameFirst::class,
                'handle' => 'firstName',
                'label' => 'First Name',
                'enabled' => true,
            ],
            [
                'type' => NameLast::class,
                'handle' => 'lastName',
                'label' => 'Last Name',
                'enabled' => true,
            ],
        ],
    ]];

    $form = formie()
        ->form(['title' => 'Query Name Permutations'])
        ->nameField('nameSingle', ['useMultipleFields' => false])
        ->nameField('nameMulti', [
            'useMultipleFields' => true,
            'rows' => $nameRows,
        ])
        ->create();

    $submissionA = formie()->submission($form)->with([
        'nameSingle' => 'Alice Smith',
        'nameMulti' => ['firstName' => 'Alice', 'lastName' => 'Smith'],
    ])->save();
    $submissionB = formie()->submission($form)->with([
        'nameSingle' => 'Bob Jones',
        'nameMulti' => ['firstName' => 'Bob', 'lastName' => 'Jones'],
    ])->save();
    $submissionC = formie()->submission($form)->with([
        'nameSingle' => 'Alice Stone',
        'nameMulti' => ['firstName' => 'Alice', 'lastName' => 'Stone'],
    ])->save();

    $singleExact = Submission::find()
        ->formId($form->id)
        ->nameSingle('Alice Smith')
        ->one();

    $singleNoMatch = Submission::find()
        ->formId($form->id)
        ->nameSingle('Missing Person')
        ->all();

    $multiExact = Submission::find()
        ->formId($form->id)
        ->nameMulti(['firstName' => 'Alice', 'lastName' => 'Smith'])
        ->one();

    $multiCollection = Submission::find()
        ->formId($form->id)
        ->nameMulti(['firstName' => 'Alice'])
        ->all();

    expect($singleExact?->id)->toBe($submissionA->id)
        ->and($multiExact?->id)->toBe($submissionA->id)
        ->and($singleNoMatch)->toHaveCount(0);

    $collectionIds = array_map(static fn(Submission $submission): int => (int)$submission->id, $multiCollection);
    sort($collectionIds, SORT_NUMERIC);

    expect($collectionIds)->toBe([$submissionA->id, $submissionC->id]);
    expect($collectionIds)->not->toContain($submissionB->id);
});
