<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;

it('queries submissions by core identifiers and form scope', function (): void {
    $form = formie()
        ->form(['title' => 'Query Core'])
        ->singleLineTextField('fullName')
        ->create();

    $submissionA = formie()
        ->submission($form)
        ->with(['fullName' => 'Alpha'])
        ->save();
    $submissionB = formie()
        ->submission($form)
        ->with(['fullName' => 'Bravo'])
        ->save();
    $submissionC = formie()
        ->submission($form)
        ->with(['fullName' => 'Charlie'])
        ->save();

    $byId = Submission::find()->id($submissionA->id)->one();
    $byUid = Submission::find()->uid($submissionA->uid)->one();
    $byFormId = Submission::find()->formId($form->id)->all();
    $byFormHandle = Submission::find()->form($form->handle)->all();

    $expectedIds = [$submissionA->id, $submissionB->id, $submissionC->id];
    sort($expectedIds, SORT_NUMERIC);

    $byFormIdIds = array_map(static fn(Submission $submission): int => (int)$submission->id, $byFormId);
    $byFormHandleIds = array_map(static fn(Submission $submission): int => (int)$submission->id, $byFormHandle);
    sort($byFormIdIds, SORT_NUMERIC);
    sort($byFormHandleIds, SORT_NUMERIC);

    expect($byId?->id)->toBe($submissionA->id)
        ->and($byUid?->id)->toBe($submissionA->id)
        ->and($byFormIdIds)->toBe($expectedIds)
        ->and($byFormHandleIds)->toBe($expectedIds);
});

it('supports core query count exists and anyStatus contracts', function (): void {
    $form = formie()
        ->form(['title' => 'Query Count'])
        ->singleLineTextField('fullName')
        ->create();

    $submissionA = formie()->submission($form)->with(['fullName' => 'Beta'])->save();
    $submissionB = formie()->submission($form)->with(['fullName' => 'Gamma'])->save();
    $submissionC = formie()->submission($form)->with(['fullName' => 'Delta'])->save();

    $count = Submission::find()->formId($form->id)->count();
    $exists = Submission::find()->id($submissionA->id)->exists();
    $anyStatus = Submission::find()->formId($form->id)->anyStatus()->all();

    $expectedIds = [$submissionA->id, $submissionB->id, $submissionC->id];
    sort($expectedIds, SORT_NUMERIC);
    $anyStatusIds = array_map(static fn(Submission $submission): int => (int)$submission->id, $anyStatus);
    sort($anyStatusIds, SORT_NUMERIC);

    expect((int)$count)->toBe(3)
        ->and($exists)->toBeTrue()
        ->and($anyStatusIds)->toBe($expectedIds);
});
