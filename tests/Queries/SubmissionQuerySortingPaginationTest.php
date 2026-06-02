<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;

it('supports ordering and pagination contracts', function (): void {
    $form = formie()
        ->form(['title' => 'Query Sorting'])
        ->singleLineTextField('fullName')
        ->create();

    $a = formie()->submission($form)->with(['fullName' => 'A'])->save();
    $b = formie()->submission($form)->with(['fullName' => 'B'])->save();
    $c = formie()->submission($form)->with(['fullName' => 'C'])->save();

    $asc = Submission::find()->formId($form->id)->orderBy(['elements.id' => SORT_ASC])->all();
    $desc = Submission::find()->formId($form->id)->orderBy(['elements.id' => SORT_DESC])->all();
    $paged = Submission::find()->formId($form->id)->orderBy(['elements.id' => SORT_ASC])->limit(1)->offset(1)->all();

    $ascIds = array_map(static fn(Submission $submission): int => (int)$submission->id, $asc);
    $descIds = array_map(static fn(Submission $submission): int => (int)$submission->id, $desc);
    $pagedIds = array_map(static fn(Submission $submission): int => (int)$submission->id, $paged);

    expect($ascIds)->toBe([$a->id, $b->id, $c->id])
        ->and($descIds)->toBe([$c->id, $b->id, $a->id])
        ->and($pagedIds)->toBe([$b->id]);
});
