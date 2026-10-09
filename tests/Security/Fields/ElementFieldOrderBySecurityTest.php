<?php

declare(strict_types=1);

use craft\elements\db\OrderByPlaceholderExpression;
use verbb\formie\fields\Entries;

it('converts declared element field ordering to a structured query value', function (): void {
    $field = new Entries(['orderBy' => 'title desc']);

    expect($field->getElementsQuery()->orderBy)->toBe(['title' => SORT_DESC]);

    $field->orderBy = 'lft ASC';

    expect($field->getElementsQuery()->orderBy)->toBe(['lft' => SORT_ASC]);
})->group('security');

it('ignores executable and undeclared element field ordering', function (string $orderBy): void {
    $field = new Entries(['orderBy' => $orderBy]);
    $queryOrderBy = $field->getElementsQuery()->orderBy;

    expect($queryOrderBy)->toHaveCount(1)
        ->and($queryOrderBy[0])->toBeInstanceOf(OrderByPlaceholderExpression::class)
        ->and($queryOrderBy[0]->expression)->toBe('');
})->with([
    'SQL expression' => '(SELECT SLEEP(5))',
    'undeclared column' => 'password ASC',
    'additional clause' => 'title ASC NULLS FIRST',
])->group('security');
