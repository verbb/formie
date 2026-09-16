<?php

declare(strict_types=1);

use verbb\formie\fields\Number;

it('exports number bounds only while limits are enabled', function (bool $limit, int|float|null $min, int|float|null $max): void {
    $field = new Number(['handle' => 'quantity', 'label' => 'Quantity', 'limit' => $limit, 'min' => $min, 'max' => $max]);
    $expected = ['type' => 'number', 'min' => $limit ? $min : null, 'max' => $limit ? $max : null];
    $payload = $field->getClientPayload();
    $input = $field->getClientInputDefinition();

    expect($field->validationRules())->toBe([$expected])
        ->and(json_decode($field->getValidationRulesJson(), true))->toBe([$expected])
        ->and($payload['validation'])->toBe([$expected])
        ->and($payload['input']['min'])->toBe($expected['min'])
        ->and($payload['input']['max'])->toBe($expected['max'])
        ->and($input['min'])->toBe($expected['min'])
        ->and($input['max'])->toBe($expected['max'])
        ->and($input['inputType'])->toBe('number');
})->with([
    'disabled retained positive bounds' => [false, 10, 20],
    'enabled positive bounds' => [true, 10, 20],
    'disabled retained zero bounds' => [false, 0, 0],
    'enabled zero bounds' => [true, 0, 0],
    'disabled retained negative fractional bounds' => [false, -2.5, -0.5],
    'enabled negative fractional bounds' => [true, -2.5, -0.5],
    'disabled null bounds' => [false, null, null],
    'enabled null bounds' => [true, null, null],
]);
