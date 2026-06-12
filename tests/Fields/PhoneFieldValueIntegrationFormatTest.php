<?php

declare(strict_types=1);

use verbb\formie\fields\values\PhoneFieldValue;
use verbb\formie\helpers\IntegrationHelper;
use verbb\formie\models\IntegrationField;

it('formats phone numbers as E164 for integration mapping', function (): void {
    $value = new PhoneFieldValue([
        'number' => '400000000',
        'country' => 'AU',
        'hasCountryCode' => true,
    ]);

    expect(PhoneFieldValue::toNormalizedPhone($value))->toBe('+61400000000');
});

it('converts TYPE_PHONE integration values through E164 formatting', function (): void {
    $value = new PhoneFieldValue([
        'number' => '4045551234',
        'country' => 'US',
        'hasCountryCode' => true,
    ]);

    $formatted = IntegrationHelper::convertValueForIntegration($value, new IntegrationField([
        'type' => IntegrationField::TYPE_PHONE,
    ]));

    expect($formatted)->toBe('+14045551234');
});
