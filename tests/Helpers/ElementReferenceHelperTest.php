<?php

declare(strict_types=1);

use craft\elements\Entry;
use verbb\formie\fields\Entries;
use verbb\formie\helpers\ElementReferenceHelper;

it('parses element property selectors with optional index', function (): void {
    expect(ElementReferenceHelper::parseSelector('title'))
        ->toBe(['title', null])
        ->and(ElementReferenceHelper::parseSelector('0:title'))
        ->toBe(['title', 0])
        ->and(ElementReferenceHelper::parseSelector('title', ['index' => 1]))
        ->toBe(['title', 1]);
});

it('resolves unindexed element property selectors across related elements', function (): void {
    $field = new Entries(['handle' => 'expert', 'labelSource' => 'title']);
    $first = new Entry(['title' => 'John (Doe)']);
    $second = new Entry(['title' => 'Jane']);

    expect(ElementReferenceHelper::resolveFromElements($field, [$first], 'title'))
        ->toBe('John (Doe)')
        ->and(ElementReferenceHelper::resolveFromElements($field, [$first, $second], 'title'))
        ->toBe('John (Doe), Jane')
        ->and(ElementReferenceHelper::resolveFromElements($field, [$first, $second], '0:title'))
        ->toBe('John (Doe)')
        ->and(ElementReferenceHelper::resolveFromElements($field, [$first, $second], '1:title'))
        ->toBe('Jane')
        ->and(ElementReferenceHelper::resolveFromElements($field, [], 'title'))
        ->toBeNull();
});

it('resolves formatted and url element reference aliases', function (): void {
    $field = new Entries(['handle' => 'expert', 'labelSource' => 'title']);
    $entry = new Entry(['title' => 'John (Doe)']);

    expect($field->resolveElementProperty($entry, '__toString'))
        ->toBe('John (Doe)')
        ->and($field->resolveElementProperty($entry, ''))
        ->toBe('John (Doe)')
        ->and($field->resolveElementProperty($entry, 'title'))
        ->toBe('John (Doe)');
});
