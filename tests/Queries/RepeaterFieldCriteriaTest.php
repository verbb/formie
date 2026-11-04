<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use verbb\formie\fields\Name;
use verbb\formie\fields\Number;
use verbb\formie\fields\SingleLineText;
use verbb\formie\fields\subfields\NameFirst;
use verbb\formie\fields\subfields\NameLast;

function repeaterCriteriaFixture(): array
{
    $rows = [['fields' => [
        ['type' => SingleLineText::class, 'handle' => 'text', 'label' => 'Text'],
        ['type' => Number::class, 'handle' => 'score', 'label' => 'Score'],
        ['type' => Name::class, 'handle' => 'person', 'label' => 'Person', 'useMultipleFields' => true, 'rows' => [['fields' => [
            ['type' => NameFirst::class, 'handle' => 'firstName', 'label' => 'First Name', 'enabled' => true],
            ['type' => NameLast::class, 'handle' => 'lastName', 'label' => 'Last Name', 'enabled' => true],
        ]]]],
    ]]];
    $form = formie()->form()->groupField('grouped', ['rows' => $rows])->repeaterField('repeated', ['rows' => $rows])->create();
    $wanted = formie()->submission($form)->with([
        'grouped' => ['text' => 'Needle', 'score' => '1'],
        'repeated' => [
            ['text' => 'Other', 'score' => '1', 'person' => ['firstName' => 'Ann', 'lastName' => 'Other']],
            ['text' => 'Needle', 'score' => '2', 'person' => ['firstName' => 'Bea', 'lastName' => 'Stone']],
        ],
    ])->save();
    $other = formie()->submission($form)->with([
        'grouped' => ['text' => 'Haystack', 'score' => '9'],
        'repeated' => [['text' => 'Haystack', 'score' => '9', 'person' => ['firstName' => 'Cal', 'lastName' => 'Reed']]],
    ])->save();
    $empty = formie()->submission($form)->with(['grouped' => ['text' => 'Empty']])->save();

    return [$form, (int)$wanted->id, (int)$other->id, (int)$empty->id];
}

function repeaterCriteriaIds(mixed $query): array
{
    $ids = array_map('intval', $query->ids());
    sort($ids);

    return $ids;
}

it('finds a saved submission from a value in any repeater row', function (): void {
    [$form, $wanted] = repeaterCriteriaFixture();

    expect(repeaterCriteriaIds(Submission::find()->formId($form->id)->field('repeated', ['text' => 'Needle'])))->toBe([$wanted])
        ->and(repeaterCriteriaIds(Submission::find()->formId($form->id)->repeated(['text' => 'Needle'])))->toBe([$wanted])
        ->and(repeaterCriteriaIds(Submission::find()->formId($form->id)->field('repeated', ['text' => 'Other'])))->toBe([$wanted])
        ->and(Submission::find()->id($wanted)->one()->getFieldValue('repeated.1.text'))->toBe('Needle');
});

it('requires every repeater criterion to match within one row', function (): void {
    [$form, $wanted] = repeaterCriteriaFixture();

    // `Needle` is in the second row and score 1 is only in the first.
    $stored = Submission::find()->id($wanted)->one()->getFieldValue('repeated');
    $split = Submission::find()->formId($form->id)->field('repeated', ['text' => 'Needle', 'score' => $stored[0]['score']]);
    $matching = Submission::find()->formId($form->id)->field('repeated', ['text' => 'Needle', 'score' => $stored[1]['score']]);

    expect($split->ids())->toBe([])
        ->and(repeaterCriteriaIds($matching))->toBe([$wanted]);
});

it('reaches fixed parent fields nested in repeater rows', function (): void {
    [$form, $wanted] = repeaterCriteriaFixture();

    expect(repeaterCriteriaIds(Submission::find()->formId($form->id)->field('repeated', ['person' => ['firstName' => 'Bea']])))->toBe([$wanted])
        ->and(Submission::find()->formId($form->id)->field('repeated', ['person' => ['firstName' => 'Bea', 'lastName' => 'Other']])->ids())->toBe([]);
});

it('compares repeater values exactly', function (string $value): void {
    [$form] = repeaterCriteriaFixture();

    expect(Submission::find()->formId($form->id)->field('repeated', ['text' => $value])->ids())->toBe([]);
})->with(['different case' => 'needle', 'partial' => 'Need', 'absent' => 'Nothing']);

it('keeps negated repeater criteria in step with positive criteria', function (): void {
    [$form, , $other, $empty] = repeaterCriteriaFixture();

    expect(repeaterCriteriaIds(Submission::find()->formId($form->id)->field('repeated', ['not', 'text' => 'Needle'])))->toBe([$other, $empty]);
});

it('ignores repeater criteria for unknown nested handles', function (): void {
    [$form, $wanted, $other, $empty] = repeaterCriteriaFixture();

    expect(repeaterCriteriaIds(Submission::find()->formId($form->id)->field('repeated', ['missing' => 'Needle'])))->toBe([$wanted, $other, $empty]);
});

it('keeps group criteria matching the stored object', function (): void {
    [$form, $wanted, $other] = repeaterCriteriaFixture();

    expect(repeaterCriteriaIds(Submission::find()->formId($form->id)->field('grouped', ['text' => 'Needle'])))->toBe([$wanted])
        ->and(repeaterCriteriaIds(Submission::find()->formId($form->id)->grouped(['text' => 'Haystack'])))->toBe([$other])
        ->and(Submission::find()->formId($form->id)->field('grouped', ['text' => 'needle'])->ids())->toBe([])
        ->and(repeaterCriteriaIds(Submission::find()->formId($form->id)->field('grouped', ['not', 'text' => 'Needle'])))->not->toContain($wanted);
});
