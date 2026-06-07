<?php

declare(strict_types=1);

use verbb\formie\helpers\RepeaterReferenceHelper;
use verbb\formie\helpers\References;
use verbb\formie\helpers\Variables;

it('normalizes repeater selector scope metadata', function (): void {
    expect(RepeaterReferenceHelper::parseSelectorAndScope('innerText', ['scope' => 'all']))
        ->toBe(['innerText', 'all', null])
        ->and(RepeaterReferenceHelper::parseSelectorAndScope('2:innerText', []))
        ->toBe(['innerText', 'index', 2])
        ->and(RepeaterReferenceHelper::parseSelectorAndScope('innerText', ['scope' => 'index', 'index' => 1]))
        ->toBe(['innerText', 'index', 1]);
});

it('parses scoped repeater field tokens', function (): void {
    $expr = References::parseReferenceExpression('{field:attendees:guestEmail;scope=last}');

    expect($expr->isValid)->toBeTrue()
        ->and($expr->identifier)->toBe('attendees')
        ->and($expr->selector)->toBe('guestEmail')
        ->and($expr->transformerParams['scope'] ?? null)->toBe('last');
});

it('builds scoped repeater field tokens', function (): void {
    expect(References::field('attendees', 'guestEmail', ['scope' => 'all']))
        ->toBe('{field:attendees:guestEmail;scope=all}');
});

it('applies array transforms', function (): void {
    expect(Variables::applyVariableTransformer(['a', 'b', 'c'], 'join', ['separator' => ' | ']))
        ->toBe('a | b | c')
        ->and(Variables::applyVariableTransformer(['a', 'b'], 'first'))
        ->toBe('a')
        ->and(Variables::applyVariableTransformer(['a', 'b'], 'last'))
        ->toBe('b')
        ->and(Variables::applyVariableTransformer(['a', 'b'], 'count'))
        ->toBe(2);
});

it('parses custom repeater row expressions as 1-based indices', function (): void {
    expect(RepeaterReferenceHelper::parseRowsExpression('1,3,5', 5))
        ->toBe([0, 2, 4])
        ->and(RepeaterReferenceHelper::parseRowsExpression('1-3,5', 5))
        ->toBe([0, 1, 2, 4])
        ->and(RepeaterReferenceHelper::parseRowsExpression('even', 4))
        ->toBe([1, 3])
        ->and(RepeaterReferenceHelper::parseRowsExpression('odd', 4))
        ->toBe([0, 2])
        ->and(RepeaterReferenceHelper::parseRowsExpression('every:2', 5))
        ->toBe([0, 2, 4]);
});

it('builds scoped repeater row tokens', function (): void {
    expect(References::field('attendees', 'guestEmail', ['scope' => 'rows', 'rows' => '1-3,5']))
        ->toBe('{field:attendees:guestEmail;scope=rows;rows=1-3%2C5}');
});

it('resolves custom repeater row selections', function (): void {
    $rows = [
        ['innerText' => 'Row One'],
        ['innerText' => 'Row Two'],
        ['innerText' => 'Row Three'],
        ['innerText' => 'Row Four'],
        ['innerText' => 'Row Five'],
    ];

    expect(RepeaterReferenceHelper::parseRowsExpression('1,3,5', count($rows)))
        ->toBe([0, 2, 4]);

    $values = array_map(static fn(array $row): string => (string)$row['innerText'], $rows);
    $indices = RepeaterReferenceHelper::parseRowsExpression('even', count($rows));
    $evenValues = array_values(array_filter(array_map(
        static fn(int $index): ?string => $values[$index] ?? null,
        $indices,
    )));

    expect($evenValues)->toBe(['Row Two', 'Row Four']);
});
