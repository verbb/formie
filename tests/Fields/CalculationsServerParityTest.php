<?php

declare(strict_types=1);

use verbb\formie\helpers\CalculationExpression;
use verbb\formie\helpers\ServerCalculations;

// The server works out Calculations answers when they set a payment amount. These cases were worked out by
// formie-core's calculations.ts, so the server has to give the browser's answer for each one, quirks included.

function calculationsParityCases(): array
{
    $cases = [];

    foreach (json_decode(file_get_contents(__DIR__ . '/../fixtures/calculations-parity.json'), true, flags: JSON_THROW_ON_ERROR) as $case) {
        $cases[$case['formula'] . ' with ' . json_encode($case['sources']) . ($case['format']['formatting'] ? ' as a number' : '')] = [$case];
    }

    return $cases;
}

it('gives the browser’s answer', function (array $case): void {
    $result = ServerCalculations::evaluateFormula($case['formula'], $case['variables'], $case['sources'], $case['format']);

    expect(is_float($result) ? CalculationExpression::toJsString($result) : $result)->toBe($case['expected']);
})->with(calculationsParityCases());

it('formats numbers as the browser does', function (float $value, int $decimals, string $expected): void {
    expect(CalculationExpression::toFixed($value, $decimals))->toBe($expected);
})->with([
    [1.005, 2, '1.00'],
    [2.675, 2, '2.67'],
    [0.25, 1, '0.3'],
    [2.5, 0, '3'],
    [-1.5, 0, '-2'],
    [1.45, 1, '1.4'],
    [9.995, 2, '9.99'],
    [99.5, 0, '100'],
    [-0.001, 2, '-0.00'],
    [37.5, 2, '37.50'],
]);

it('prints numbers as the browser does', function (float $value, string $expected): void {
    expect(CalculationExpression::toJsString($value))->toBe($expected);
})->with([
    [0.1 + 0.2, '0.30000000000000004'],
    [10.0, '10'],
    [37.5, '37.5'],
    [-0.5, '-0.5'],
    [1.0e21, '1e+21'],
    [1.0e-7, '1e-7'],
    [0.000001, '0.000001'],
    [123456789.125, '123456789.125'],
]);

it('compares a posted answer with the server answer', function (mixed $posted, mixed $calculated, bool $matches): void {
    expect(ServerCalculations::answersMatch($posted, $calculated))->toBe($matches);
})->with([
    'the same text' => ['Ada Lovelace', 'Ada Lovelace', true],
    'different text' => ['abc', 'abd', false],
    'a number printed by the browser' => ['0.30000000000000004', 0.1 + 0.2, true],
    'a number formatted for display' => ['$6.00', 6.0, true],
    'a different number' => ['$1.00', 6.0, false],
    'no answer either way' => ['NaN', '', true],
    'no answer and zero' => ['', '0', false],
]);
