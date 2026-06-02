<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;

it('queries number and date fields with deterministic operator boundaries', function (): void {
    $form = formie()
        ->form(['title' => 'Query Operator Boundaries'])
        ->numberField('score')
        ->dateField('dob')
        ->create();

    $submissionA = formie()->submission($form)->with([
        'score' => '10',
        'dob' => '2026-01-01',
    ])->save();
    $submissionB = formie()->submission($form)->with([
        'score' => '20',
        'dob' => '2026-01-15',
    ])->save();
    $submissionC = formie()->submission($form)->with([
        'score' => '30',
        'dob' => '2026-02-01',
    ])->save();

    $ids = static fn(array $submissions): array => array_map(static fn(Submission $submission): int => (int)$submission->id, $submissions);

    $gt20 = Submission::find()->formId($form->id)->field('score', '> 20')->orderBy(['elements.id' => SORT_ASC])->all();
    $gte20 = Submission::find()->formId($form->id)->field('score', '>= 20')->orderBy(['elements.id' => SORT_ASC])->all();
    $lt20 = Submission::find()->formId($form->id)->field('score', '< 20')->orderBy(['elements.id' => SORT_ASC])->all();
    $lte20 = Submission::find()->formId($form->id)->field('score', '<= 20')->orderBy(['elements.id' => SORT_ASC])->all();

    expect($ids($gt20))->toBe([$submissionC->id]);
    expect($ids($gte20))->toBe([$submissionB->id, $submissionC->id]);
    expect($ids($lt20))->toBe([$submissionA->id]);
    expect($ids($lte20))->toBe([$submissionA->id, $submissionB->id]);

    $dateWindow = Submission::find()
        ->formId($form->id)
        ->field('dob', ['and', '>= 2026-01-15', '< 2026-02-01'])
        ->all();
    $dateFromBoundary = Submission::find()
        ->formId($form->id)
        ->field('dob', '>= 2026-01-15')
        ->orderBy(['elements.id' => SORT_ASC])
        ->all();

    expect($dateWindow)->toHaveCount(1)
        ->and($dateWindow[0]->id)->toBe($submissionB->id)
        ->and($ids($dateFromBoundary))->toBe([$submissionB->id, $submissionC->id]);
});
