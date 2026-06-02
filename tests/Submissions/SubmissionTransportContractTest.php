<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;

it('keeps submission find query transport-facing contracts callable', function (): void {
    $form = formie()
        ->form(['title' => 'Submission Transport Contract'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()
        ->submission($form)
        ->with(['fullName' => 'Transport'])
        ->save();

    $results = Submission::find()
        ->formId($form->id)
        ->id($submission->id)
        ->siteId('*')
        ->all();

    expect($results)->toHaveCount(1)
        ->and($results[0]->id)->toBe($submission->id)
        ->and($results[0]->formId)->toBe($form->id);
});

it('keeps before and after date query params callable for submissions', function (): void {
    $form = formie()
        ->form(['title' => 'Submission Date Filters'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()->submission($form)->with(['fullName' => 'Date Filter'])->save();

    $after = Submission::find()->formId($form->id)->after('2000-01-01')->all();
    $before = Submission::find()->formId($form->id)->before('2999-01-01')->all();
    $futureAfter = Submission::find()->formId($form->id)->after('2999-01-01')->all();

    $afterIds = array_map(static fn(Submission $item): int => (int)$item->id, $after);
    $beforeIds = array_map(static fn(Submission $item): int => (int)$item->id, $before);

    expect($after)->toBeArray()
        ->and($before)->toBeArray()
        ->and(in_array((int)$submission->id, $afterIds, true))->toBeTrue()
        ->and(in_array((int)$submission->id, $beforeIds, true))->toBeTrue()
        ->and($futureAfter)->toHaveCount(0);
});
