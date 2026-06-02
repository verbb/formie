<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;

it('enforces exact one and all query result contracts', function (): void {
    $form = formie()
        ->form(['title' => 'Query One All Contract'])
        ->singleLineTextField('fullName')
        ->singleLineTextField('segment')
        ->create();

    $submissionA = formie()->submission($form)->with([
        'fullName' => 'Needle A',
        'segment' => 'alpha',
    ])->save();
    $submissionB = formie()->submission($form)->with([
        'fullName' => 'Needle B',
        'segment' => 'alpha',
    ])->save();
    $submissionC = formie()->submission($form)->with([
        'fullName' => 'Needle C',
        'segment' => 'beta',
    ])->save();

    $assertIds = static function(array $submissions, array $expectedIds): void {
        $actualIds = array_map(static fn(Submission $submission): int => (int)$submission->id, $submissions);
        sort($actualIds, SORT_NUMERIC);
        sort($expectedIds, SORT_NUMERIC);

        expect($actualIds)->toBe($expectedIds);
    };

    $byId = Submission::find()->id($submissionA->id)->one();
    $byUid = Submission::find()->uid($submissionB->uid)->one();
    $byFieldOne = Submission::find()
        ->formId($form->id)
        ->field('fullName', 'Needle C')
        ->one();

    expect($byId?->id)->toBe($submissionA->id)
        ->and($byUid?->id)->toBe($submissionB->id)
        ->and($byFieldOne?->id)->toBe($submissionC->id);

    $byFormIdAll = Submission::find()->formId($form->id)->all();
    $byFormHandleAll = Submission::find()->form($form->handle)->all();
    $byFieldAll = Submission::find()
        ->formId($form->id)
        ->segment('alpha')
        ->all();

    $assertIds($byFormIdAll, [$submissionA->id, $submissionB->id, $submissionC->id]);
    $assertIds($byFormHandleAll, [$submissionA->id, $submissionB->id, $submissionC->id]);
    $assertIds($byFieldAll, [$submissionA->id, $submissionB->id]);
});
