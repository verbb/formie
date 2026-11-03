<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\conditions\ConditionSet;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;

// Rows with these exact shapes are stored on upgraded Formie 3 forms, left behind by "add rule" in the builder.
function blankConditionRows(): array
{
    return [
        'nothing filled in' => [[['id' => 'new1', 'field' => '', 'value' => '', 'condition' => '', 'legacyForward' => true]]],
        'a value but no field or operator' => [[['id' => 'new1', 'field' => '', 'value' => 'test', 'condition' => '', 'legacyForward' => true]]],
        'an operator but no field or value' => [[['id' => 'new1', 'field' => '', 'value' => '', 'condition' => '=', 'legacyForward' => true]]],
    ];
}

function blankConditionSettings(array $rows, string $mode = 'all', string $effect = 'show'): array
{
    return ['version' => 1, 'showRule' => $effect, 'conditionRule' => $mode, 'conditions' => $rows];
}

function blankConditionRender(Form $form): string
{
    return WebRequestTestHelper::withWebRequestContext(static function () use ($form): string {
        return (string)Formie::$plugin->getRendering()->renderForm($form);
    });
}

it('renders a saved form whose field has a blank condition row', function (array $rows): void {
    $saved = formie()->form(['title' => 'Blank condition row'])
        ->singleLineTextField('target', ['enableConditions' => true, 'conditions' => blankConditionSettings($rows)])
        ->create();

    $form = Form::find()->id($saved->id)->one();
    $html = blankConditionRender($form);

    expect($html)->toContain('<form')
        ->and($html)->toContain('fields[target]')
        ->and($form->getFieldByHandle('target')->getBrowserConditions())->toBe([]);
})->with(blankConditionRows());

it('renders a saved form whose page has a blank condition row', function (array $rows): void {
    $saved = formie()->form(['title' => 'Blank page condition row'])
        ->multiPage(2)
        ->onPage(1)->singleLineTextField('first')
        ->onPage(2)->singleLineTextField('second')
        ->create();

    $form = Form::find()->id($saved->id)->one();
    $page = $form->getPages()[1];
    $page->getPageSettings()->enablePageConditions = true;
    $page->getPageSettings()->pageConditions = blankConditionSettings($rows);

    expect($page->hasConditions())->toBeTrue()
        ->and(blankConditionRender($form))->toContain('<form');
})->with(blankConditionRows());

it('treats a blank row as absent when working out visibility', function (array $rows, string $mode, bool $hidden): void {
    $saved = formie()->form(['title' => 'Blank condition visibility'])
        ->singleLineTextField('target', ['enableConditions' => true, 'conditions' => blankConditionSettings($rows, $mode)])
        ->create();

    $form = Form::find()->id($saved->id)->one();
    $submission = new Submission();
    $submission->setForm($form);

    // Formie 3 results: with no rules left, "all" passes and "any" doesn't.
    expect($form->getFieldByHandle('target')->isConditionallyHidden($submission))->toBe($hidden);
})->with(function () {
    foreach (blankConditionRows() as $name => [$rows]) {
        yield "$name, all" => [$rows, 'all', false];
        yield "$name, any" => [$rows, 'any', true];
    }
});

it('still applies a real rule next to a blank row', function (string $value, bool $hidden): void {
    $saved = formie()->form(['title' => 'Blank row with real rule'])
        ->singleLineTextField('source')
        ->singleLineTextField('target')
        ->create();

    $form = Form::find()->id($saved->id)->one();
    $source = $form->getFieldByHandle('source');
    $target = $form->getFieldByHandle('target');
    $target->enableConditions = true;
    $target->conditions = blankConditionSettings([
        ['id' => 'new1', 'field' => '', 'value' => '', 'condition' => ''],
        ['id' => 'new2', 'field' => '{field:' . $source->reference . '}', 'value' => 'yes', 'condition' => '='],
    ]);

    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValue('source', $value);

    $browser = $target->getBrowserConditions();

    expect($target->isConditionallyHidden($submission))->toBe($hidden)
        ->and($browser['rules'])->toHaveCount(1)
        ->and($browser['rules'][0]['field'])->toBe('{field:' . $source->reference . '}');
})->with([
    'matching' => ['yes', false],
    'not matching' => ['no', true],
]);

it('keeps rows that are not blank as they were', function (): void {
    $set = ConditionSet::fromArray(blankConditionSettings([
        ['field' => '{field:known}', 'value' => '', 'condition' => 'empty'],
        ['field' => 'known', 'value' => 'x', 'condition' => '='],
        ['field' => '', 'value' => 'x', 'condition' => '='],
    ]));

    expect(array_map(static fn($rule) => $rule->reference, $set->rules))->toBe(['{field:known}', 'known', '']);
});

it('leaves malformed rows invalid', function (): void {
    foreach ([[['value' => 'x', 'condition' => '=']], [['field' => null, 'value' => 'x', 'condition' => '=']], [['field' => [], 'condition' => '=']], ['not a row']] as $rows) {
        $set = ConditionSet::fromArray(blankConditionSettings($rows));
        $result = (new \verbb\formie\conditions\ConditionSetEvaluator())->evaluate($set, new Submission());

        expect($set->rules)->toHaveCount(1)
            ->and($result->value)->toBeNull()
            ->and($result->diagnostics)->not->toBeEmpty();
    }
});
