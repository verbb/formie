<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;

it('applies filtering with deterministic order, limit, and offset', function (): void {
    $form = formie()
        ->form(['title' => 'Query Filter Limit Contract'])
        ->singleLineTextField('segment')
        ->singleLineTextField('label')
        ->create();

    $submissionA = formie()->submission($form)->with(['segment' => 'group-a', 'label' => 'A'])->save();
    $submissionB = formie()->submission($form)->with(['segment' => 'group-a', 'label' => 'B'])->save();
    $submissionC = formie()->submission($form)->with(['segment' => 'group-a', 'label' => 'C'])->save();
    $submissionD = formie()->submission($form)->with(['segment' => 'group-b', 'label' => 'D'])->save();

    $groupAAll = Submission::find()
        ->formId($form->id)
        ->segment('group-a')
        ->orderBy(['elements.id' => SORT_ASC])
        ->all();

    $groupASecondOnly = Submission::find()
        ->formId($form->id)
        ->segment('group-a')
        ->orderBy(['elements.id' => SORT_ASC])
        ->limit(1)
        ->offset(1)
        ->all();

    $groupBOnly = Submission::find()
        ->formId($form->id)
        ->segment('group-b')
        ->orderBy(['elements.id' => SORT_ASC])
        ->all();

    $noMatches = Submission::find()
        ->formId($form->id)
        ->segment('missing-group')
        ->limit(2)
        ->offset(0)
        ->all();

    expect(array_map(static fn(Submission $submission): int => (int)$submission->id, $groupAAll))
        ->toBe([$submissionA->id, $submissionB->id, $submissionC->id]);

    expect(array_map(static fn(Submission $submission): int => (int)$submission->id, $groupASecondOnly))
        ->toBe([$submissionB->id]);

    expect(array_map(static fn(Submission $submission): int => (int)$submission->id, $groupBOnly))
        ->toBe([$submissionD->id]);

    expect($noMatches)->toHaveCount(0);
});
