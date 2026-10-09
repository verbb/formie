<?php

use craft\helpers\StringHelper;
use verbb\formie\elements\{Form, Submission};
use verbb\formie\fields\{Checkboxes, Dropdown, Entries, Recipients};
use verbb\formie\models\FormInstanceConfig;

function queryPrefillValue($field, mixed $input): mixed
{
    $field->uid = StringHelper::UUID();
    $form = new Form();
    $form->replaceInstanceConfig(new FormInstanceConfig(prefill: [$field->uid => $input]));
    $submission = new Submission();
    $submission->setForm($form);
    return $field->getPrefillValue($submission);
}

it('normalizes query choices without splitting submitted array values', function (mixed $input, array $expected): void {
    $field = new Checkboxes(['options' => [['label' => 'A', 'value' => 'a'], ['label' => 'B', 'value' => 'b'], ['label' => 'Comma', 'value' => 'a,b']]]);
    expect(queryPrefillValue($field, $input)->values())->toBe($expected);
})->with([['a,b', ['a', 'b']], [['a,b'], ['a,b']], [['a', 'b'], ['a', 'b']]]);

it('resolves a recipient label for prefill without accepting it as a submitted address', function (): void {
    $field = new Recipients(['displayType' => 'dropdown', 'options' => [['label' => 'Sales', 'value' => 'sales@example.test']]]);
    expect(queryPrefillValue($field, 'Sales')->rawValue())->toBe('sales@example.test');
    expect($field->normalizeValueFromRequest('Sales', null)->valid())->toBeFalse();
});

it('accepts scalar and array element ids as query prefill', function (mixed $input, array $expected): void {
    expect(queryPrefillValue(new Entries(), $input)->id)->toBe($expected);
})->with([['123', ['123']], [['123', '', '456'], ['123', '456']]]);

it('retains row markers as well as field errors for duplicate options', function (): void {
    $field = new Dropdown(['options' => [['label' => 'One', 'value' => 'one'], ['label' => 'One', 'value' => 'one']]]);
    $field->validateOptions();
    expect($field->getErrors('options'))->toHaveCount(2)
        ->and($field->options()[1]['label'])->toBe(['value' => 'One', 'hasErrors' => true])
        ->and($field->options()[1]['value'])->toBe(['value' => 'one', 'hasErrors' => true]);
});
