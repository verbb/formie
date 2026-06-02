<?php

declare(strict_types=1);

use verbb\formie\fields\Calculations;
use verbb\formie\fields\Payment;
use verbb\formie\fields\Signature;
use verbb\formie\fields\Table;
use verbb\formie\fields\values\ColorFieldValue;
use verbb\formie\fields\values\PaymentFieldValue;

it('normalizes table cell payloads into canonical per-column values', function (): void {
    $field = new Table([
        'handle' => 'tableData',
        'columns' => [
            'col1' => ['heading' => 'Item', 'handle' => 'item', 'type' => 'singleline'],
            'col2' => ['heading' => 'Color', 'handle' => 'color', 'type' => 'color'],
        ],
        'defaults' => [[
            'col1' => 'Default Item',
            'col2' => '#fff',
        ]],
    ]);

    $value = $field->normalizeValue([[
        'item' => ' Widget ',
        'color' => '#0f0',
    ]], null);

    expect($value)->toBeArray()
        ->and($value[0]['col1'])->toBe('Widget')
        ->and($value[0]['item'])->toBe('Widget')
        ->and($value[0]['col2'])->toBeInstanceOf(ColorFieldValue::class)
        ->and($value[0]['color'])->toBeInstanceOf(ColorFieldValue::class)
        ->and($value[0]['col2']->getHex())->toBe('#00ff00')
        ->and($field->getValueAsString($value, null))->toBe('Widget, #00ff00');
});

it('keeps table serialization stable for specialized cell value objects', function (): void {
    $field = new Table([
        'handle' => 'tableData',
        'columns' => [
            'col1' => ['heading' => 'Item', 'handle' => 'item', 'type' => 'singleline'],
            'col2' => ['heading' => 'Color', 'handle' => 'color', 'type' => 'color'],
        ],
    ]);

    $value = $field->normalizeValue([[
        'item' => 'Widget',
        'color' => '#0f0',
    ]], null);

    $serialized = $field->serializeValue($value, null);

    expect($serialized)->toBeArray()
        ->and($serialized[0])->toHaveKey('col1')
        ->and($serialized[0])->toHaveKey('col2')
        ->and($serialized[0]['col1'])->toBe('Widget')
        ->and($serialized[0]['col2'])->toBe('#00ff00');
});

it('normalizes payment values to payment data with stable string and json projections', function (): void {
    $field = new Payment([
        'handle' => 'payment',
    ]);

    $value = $field->normalizeValue([
        'amount' => '10.00',
        'currency' => 'USD',
    ], null);

    expect($value)->toBeInstanceOf(PaymentFieldValue::class)
        ->and($field->getValueAsString($value, null))->toBe('{"amount":"10.00","currency":"USD"}')
        ->and($field->getValueAsArray($value, null))->toBe([
            'amount' => '10.00',
            'currency' => 'USD',
        ])
        ->and($field->serializeValue($value, null))->toBe([
            'amount' => '10.00',
            'currency' => 'USD',
        ]);
});

it('keeps signature and calculations scalar projections stable', function (): void {
    $signature = new Signature(['handle' => 'signature']);
    $calc = new Calculations(['handle' => 'calc', 'formula' => []]);

    $signatureValue = 'data:image/png;base64,abc';
    $calcValue = $calc->normalizeValue('12.50', null);

    expect($signature->getValueAsString($signatureValue, null))->toBe('data:image/png;base64,abc')
        ->and((string)$signature->getValueForSummary($signatureValue, null))->toContain('<img')
        ->and((string)$signature->getValueForSummary($signatureValue, null))->toContain('data:image/png;base64,abc')
        ->and($calc->getFormula())->toBe([
            'expression' => '',
            'formula' => '',
            'variables' => [],
        ])
        ->and($calc->serializeValue($calcValue, null))->toBe('12.50')
        ->and($calc->getValueAsString($calcValue, null))->toBe('12.50');
});
