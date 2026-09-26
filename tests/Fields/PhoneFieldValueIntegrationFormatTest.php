<?php

declare(strict_types=1);

use verbb\formie\fields\Phone;
use verbb\formie\fields\coercion\PhoneValueCoercer;
use verbb\formie\helpers\IntegrationHelper;
use verbb\formie\models\IntegrationField;

it('formats phone numbers as E164 for integration mapping', function (): void {
    $value = (new Phone())->normalizeFieldValue([
        'number' => '400000000',
        'country' => 'AU',
    ]);

    expect(PhoneValueCoercer::toPhoneString($value))->toBe('+61400000000');
});

it('converts TYPE_PHONE integration values through E164 formatting', function (): void {
    $value = (new Phone())->normalizeFieldValue([
        'number' => '4045551234',
        'country' => 'US',
    ]);

    $formatted = IntegrationHelper::convertValueForIntegration($value, new IntegrationField([
        'type' => IntegrationField::TYPE_PHONE,
    ]));

    expect($formatted)->toBe('+14045551234');
});
