<?php
use verbb\formie\conditions\ConditionOperator;
use verbb\formie\conditions\ConditionEvaluation;

it('matches every shared browser operator fixture', function (array $fixture) {
    $result = ConditionOperator::evaluate($fixture['operator'], $fixture['actual'], $fixture['expected'], $fixture['type']);
    expect($result->value)->toBe($fixture['result']);
    expect($result->diagnostics !== [])->toBe($fixture['result'] === null);
})->with(array_map(fn($fixture) => [$fixture], json_decode(file_get_contents(dirname(__DIR__) . '/fixtures/conditions.json'), true)));

it('never inverts invalid into permission', function () {
    $invalid = ConditionEvaluation::invalid('unresolvedReference');
    expect($invalid->permits())->toBeFalse()->and($invalid->permits(false))->toBeFalse()
        ->and($invalid->hides('show'))->toBeTrue()->and($invalid->hides('hide'))->toBeFalse();
});

it('retains configured empty sets in both browser products', function () {
    foreach (['all' => true, 'any' => false] as $mode => $expected) {
        $wire = \verbb\formie\helpers\ConditionsHelper::toComponentConditionDefinition(['conditionRule' => $mode, 'showRule' => 'show', 'conditions' => []]);
        expect($wire['mode'])->toBe($mode)->and($wire['rules'])->toBe([]);
        $result = (new \verbb\formie\conditions\ConditionSetEvaluator())->evaluate(\verbb\formie\conditions\ConditionSet::fromArray($wire), new \verbb\formie\elements\Submission());
        expect($result->value)->toBe($expected);
    }
});

it('keeps malformed persisted types invalid without PHP coercion or notices', function () {
    foreach ([['mode' => []], ['effect' => []], ['version' => '1'], ['rules' => [['operator' => [], 'field' => 'missing']]]] as $settings) {
        $set = \verbb\formie\conditions\ConditionSet::fromArray($settings);
        $result = (new \verbb\formie\conditions\ConditionSetEvaluator())->evaluate($set, new \verbb\formie\elements\Submission());
        expect($result->value)->toBeNull()->and($result->diagnostics)->not->toBeEmpty();
    }
});

it('keeps numeric text independent of PHP display and serialization precision', function () {
    $precision = ini_get('serialize_precision');
    try {
        ini_set('serialize_precision', '5');
        expect(ConditionOperator::evaluate('=', 1.2345678901234567, '1.2345678901234567')->value)->toBeTrue();
        expect(ini_get('serialize_precision'))->toBe('5');
        foreach ([INF, -INF, NAN] as $value) {
            expect(ConditionOperator::evaluate('=', $value, $value)->value)->toBeNull();
        }
    } finally {
        ini_set('serialize_precision', $precision);
    }
});
