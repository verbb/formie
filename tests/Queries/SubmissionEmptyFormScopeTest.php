<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;

it('fails closed for an empty form scope while preserving explicit and unrestricted queries', function (): void {
    $first = formie()->form()->singleLineTextField('message')->create();
    $second = formie()->form()->singleLineTextField('message')->create();
    $one = formie()->submission($first)->with(['message' => 'First secret'])->save();
    $two = formie()->submission($second)->with(['message' => 'Second secret'])->save();
    expect(Submission::find()->formId(false)->ids())->toBe([]);
    expect(Submission::find()->formId([])->ids())->toBe([]);
    expect(Submission::find()->form('missingFormForScopeAudit')->ids())->toBe([]);
    expect(array_map('intval', Submission::find()->formId($first->id)->ids()))->toBe([(int)$one->id]);
    expect(array_map('intval', Submission::find()->formId(null)->id([$one->id, $two->id])->orderBy(['elements.id' => SORT_ASC])->ids()))->toBe([(int)$one->id, (int)$two->id]);
})->group('security');
