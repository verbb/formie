<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use yii\base\UnknownMethodException;

it('filters by explicit field criteria API', function (): void {
    $form = formie()
        ->form(['title' => 'Query Field Criteria'])
        ->singleLineTextField('fullName')
        ->create();

    $alice = formie()->submission($form)->with(['fullName' => 'Alice'])->save();
    formie()->submission($form)->with(['fullName' => 'Bob'])->save();

    $results = Submission::find()
        ->formId($form->id)
        ->field('fullName', 'Alice')
        ->all();

    expect($results)->toHaveCount(1);
    expect($results[0]->id)->toBe($alice->id);
});

it('supports dynamic handle query criteria on submissions', function (): void {
    $form = formie()
        ->form(['title' => 'Dynamic Handle Query'])
        ->singleLineTextField('fullName')
        ->create();

    $charlie = formie()->submission($form)->with(['fullName' => 'Charlie'])->save();
    formie()->submission($form)->with(['fullName' => 'Delta'])->save();

    $results = Submission::find()
        ->formId($form->id)
        ->fullName('Charlie')
        ->all();

    expect($results)->toHaveCount(1);
    expect($results[0]->id)->toBe($charlie->id);
});

it('returns no results for non-matching dynamic handle criteria', function (): void {
    $form = formie()
        ->form(['title' => 'Dynamic Handle No Match'])
        ->singleLineTextField('fullName')
        ->create();

    formie()->submission($form)->with(['fullName' => 'Echo'])->save();
    formie()->submission($form)->with(['fullName' => 'Foxtrot'])->save();

    $results = Submission::find()
        ->formId($form->id)
        ->fullName('No Match')
        ->all();

    expect($results)->toHaveCount(0);
});

it('keeps unknown explicit field criteria non-fatal and scoped to form results', function (): void {
    $form = formie()
        ->form(['title' => 'Unknown Field Criteria'])
        ->singleLineTextField('fullName')
        ->create();

    $submissionA = formie()->submission($form)->with(['fullName' => 'Delta'])->save();
    $submissionB = formie()->submission($form)->with(['fullName' => 'Echo'])->save();
    $submissionC = formie()->submission($form)->with(['fullName' => 'Foxtrot'])->save();

    $results = Submission::find()
        ->formId($form->id)
        ->field('unknownFieldHandle', 'x')
        ->all();

    $ids = array_map(static fn(Submission $submission): int => (int)$submission->id, $results);
    sort($ids, SORT_NUMERIC);

    $expected = [$submissionA->id, $submissionB->id, $submissionC->id];
    sort($expected, SORT_NUMERIC);

    expect($ids)->toBe($expected);
});

it('throws for unknown dynamic handle methods', function (): void {
    $form = formie()
        ->form(['title' => 'Unknown Dynamic Handle'])
        ->singleLineTextField('fullName')
        ->create();

    formie()->submission($form)->with(['fullName' => 'Zulu'])->save();

    expect(function() use ($form): void {
        Submission::find()
            ->formId($form->id)
            ->doesNotExist('anything')
            ->all();
    })->toThrow(UnknownMethodException::class);
});
