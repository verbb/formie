<?php

declare(strict_types=1);

use verbb\formie\fields\values\AddressFieldValue;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\integrations\emailmarketing\Mailchimp;
use verbb\formie\models\IntegrationField;

it('includes Mailchimp address merge fields in custom field settings', function (): void {
    $integration = new Mailchimp(['name' => 'Mailchimp', 'handle' => 'mailchimp']);
    $method = new ReflectionMethod($integration, '_getCustomFields');
    $method->setAccessible(true);

    $fields = $method->invoke($integration, [
        ['type' => 'address', 'tag' => 'ADDRESS', 'name' => 'Address', 'required' => false],
        ['type' => 'text', 'tag' => 'FNAME', 'name' => 'First Name', 'required' => false],
    ]);

    $addressField = ArrayHelper::firstWhere($fields, 'handle', 'ADDRESS');

    expect($addressField)->not->toBeNull()
        ->and($addressField->sourceType)->toBe('address');
});

it('formats AddressFieldValue objects for Mailchimp address merge fields', function (): void {
    $method = new ReflectionMethod(Mailchimp::class, '_formatAddressMergeField');
    $method->setAccessible(true);

    $formatted = $method->invoke(null, new AddressFieldValue([
        'address1' => '123 Main St',
        'address2' => 'Suite 4',
        'city' => 'Melbourne',
        'state' => 'VIC',
        'zip' => '3000',
        'country' => 'AU',
    ]));

    expect($formatted)->toBe([
        'addr1' => '123 Main St',
        'addr2' => 'Suite 4',
        'city' => 'Melbourne',
        'state' => 'VIC',
        'zip' => '3000',
        'country' => 'AU',
    ]);
});

it('converts full country names to ISO codes for Mailchimp address merge fields', function (): void {
    $method = new ReflectionMethod(Mailchimp::class, '_formatAddressMergeField');
    $method->setAccessible(true);

    $formatted = $method->invoke(null, new AddressFieldValue([
        'address1' => '1 Test Lane',
        'city' => 'Atlanta',
        'state' => 'Georgia',
        'zip' => '30308',
        'countryOption' => 'United States',
    ]));

    expect($formatted['country'])->toBe('US');
});
