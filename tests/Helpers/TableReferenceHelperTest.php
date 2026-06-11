<?php

declare(strict_types=1);

use verbb\formie\fields\Table;
use verbb\formie\helpers\RepeaterReferenceHelper;
use verbb\formie\helpers\TableReferenceHelper;

it('maps table column handles to column ids for references', function (): void {
    $field = new Table([
        'columns' => [
            'col1' => ['heading' => 'Ticket', 'handle' => 'ticket', 'type' => 'singleline'],
            'col3' => ['heading' => 'Qty', 'handle' => 'qty', 'type' => 'number'],
        ],
    ]);

    expect(TableReferenceHelper::getColumnReferenceSelector($field, 'qty'))->toBe('col3')
        ->and(TableReferenceHelper::getColumnReferenceSelector($field, 'col3'))->toBe('col3');
});

it('parses table column selectors with row scope metadata', function (): void {
    expect(RepeaterReferenceHelper::parseSelectorAndScope('col3', ['scope' => 'all']))
        ->toBe(['col3', 'all', null])
        ->and(RepeaterReferenceHelper::parseSelectorAndScope('col3', ['scope' => 'index', 'index' => 1]))
        ->toBe(['col3', 'index', 1])
        ->and(RepeaterReferenceHelper::parseSelectorAndScope('col3', ['scope' => 'rows', 'rows' => '1,3']))
        ->toBe(['col3', 'rows', null]);
});
