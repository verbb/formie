<?php

declare(strict_types=1);

use verbb\formie\fields\Table;

// Checkbox cells read the same whether they came from a form post, a client-rendered form or the API.
it('stores checkbox cells as a form post does', function(mixed $sent, ?string $stored): void {
    $field = new Table(['handle' => 'schedule', 'columns' => [
        'col1' => ['heading' => 'Task', 'handle' => 'task', 'type' => 'singleline'],
        'col2' => ['heading' => 'Done', 'handle' => 'done', 'type' => 'checkbox'],
    ]]);

    foreach ([$field->normalizeValueFromRequest([['task' => 'Plan', 'done' => $sent]], null), $field->normalizeValue([['col1' => 'Plan', 'col2' => $sent]], null)] as $value) {
        expect($field->serializeValue($value, null))->toBe([['col1' => 'Plan', 'col2' => $stored]]);
    }
})->with([
    'form post, checked' => ['1', '1'],
    'boolean, checked' => [true, '1'],
    'boolean, unchecked' => [false, null],
    'missing' => [null, null],
    'empty' => ['', null],
]);
