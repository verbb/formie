<?php

declare(strict_types=1);

use verbb\formie\base\Field;
use verbb\formie\fields\Calculations;
use verbb\formie\fields\FileUpload;
use verbb\formie\fields\Number;
use verbb\formie\fields\Payment;
use verbb\formie\fields\Signature;
use verbb\formie\fields\SingleLineText;
use verbb\formie\fields\Table;

function assertRoundTripDeterminism(Field $field, mixed $input): void
{
    $normalizedA = $field->normalizeValue($input, null);
    $serializedA = $field->serializeValue($normalizedA, null);
    $projectedA = $field->getValueAsString($normalizedA, null);

    $normalizedB = $field->normalizeValue($serializedA, null);
    $serializedB = $field->serializeValue($normalizedB, null);
    $projectedB = $field->getValueAsString($normalizedB, null);

    expect($serializedB)->toEqual($serializedA)
        ->and($projectedB)->toBe($projectedA);
}

it('keeps empty/null/default normalization and serialization deterministic across field families', function (): void {
    assertRoundTripDeterminism(new SingleLineText(['handle' => 'name']), null);
    assertRoundTripDeterminism(new SingleLineText(['handle' => 'name']), '');
    assertRoundTripDeterminism(new Number(['handle' => 'score']), null);
    assertRoundTripDeterminism(new Number(['handle' => 'score']), '');
    assertRoundTripDeterminism(new Payment(['handle' => 'payment']), null);
    assertRoundTripDeterminism(new Signature(['handle' => 'signature']), null);
    assertRoundTripDeterminism(new Calculations(['handle' => 'calc', 'formula' => []]), null);
    assertRoundTripDeterminism(new FileUpload(['handle' => 'uploads', 'limitFiles' => false]), []);
    assertRoundTripDeterminism(new Table([
        'handle' => 'tableData',
        'columns' => [
            'col1' => ['heading' => 'Item', 'handle' => 'item', 'type' => 'singleline'],
            'col2' => ['heading' => 'Color', 'handle' => 'color', 'type' => 'color'],
        ],
        'defaults' => [[
            'col1' => 'Default Item',
            'col2' => '#fff',
        ]],
    ]), null);
});
