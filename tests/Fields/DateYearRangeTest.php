<?php

declare(strict_types=1);

use verbb\formie\fields\Date;
use verbb\formie\fields\subfields\DateYearDropdown;

it('preserves signed inclusive year offsets used by historical date dropdowns', function (int $start, int $end, array $years): void {
    $parent = new Date(['defaultOption' => 'date', 'defaultValue' => '2024-06-15 12:00:00']);
    $field = new DateYearDropdown(['minYearRange' => $start, 'maxYearRange' => $end]);
    $field->setParentField($parent);

    expect(array_column($field->options(), 'value'))->toBe(['', ...$years]);
})->with([
    'past and future' => [-2, 1, [2022, 2023, 2024, 2025]],
    'future only' => [1, 2, [2025, 2026]],
    'past only' => [-2, -1, [2022, 2023]],
    'one year' => [0, 0, [2024]],
    'reversed' => [2, 1, []],
]);
