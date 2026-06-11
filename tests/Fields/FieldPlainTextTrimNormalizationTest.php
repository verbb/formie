<?php

declare(strict_types=1);

use verbb\formie\fields\Address;
use verbb\formie\fields\Email;
use verbb\formie\fields\Hidden;
use verbb\formie\fields\MultiLineText;
use verbb\formie\fields\Name;
use verbb\formie\fields\Number;
use verbb\formie\fields\Password;
use verbb\formie\fields\Phone;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\IntegrationHelper;
use verbb\formie\models\IntegrationField;

it('trims leading and trailing whitespace from single-line text fields during normalization', function (): void {
    $field = new SingleLineText(['handle' => 'message']);

    expect($field->normalizeValue('  hello  ', null))->toBe('hello')
        ->and($field->normalizeValue('   ', null))->toBeNull();
});

it('trims email values during normalization', function (): void {
    $field = new Email(['handle' => 'email']);

    expect($field->normalizeValue(' person@example.test ', null))->toBe('person@example.test');
});

it('trims plain multi-line text but preserves rich text content', function (): void {
    $plain = new MultiLineText(['handle' => 'notes', 'useRichText' => false]);
    $rich = new MultiLineText(['handle' => 'notesRich', 'useRichText' => true]);

    expect($plain->normalizeValue("  line one\nline two  ", null))->toBe("line one\nline two")
        ->and($rich->normalizeValue('<p> spaced </p>', null))->toBe('<p> spaced </p>');
});

it('does not trim password values during normalization', function (): void {
    $field = new Password(['handle' => 'password']);

    expect($field->normalizeValue(' secret ', null))->toBe(' secret ');
});

it('trims number values before persistence', function (): void {
    $field = new Number(['handle' => 'amount']);

    expect($field->normalizeValue(' 42 ', null))->toBe('42');
});

it('trims hidden and phone field values during normalization', function (): void {
    $hidden = new Hidden(['handle' => 'token']);
    $phone = new Phone(['handle' => 'phone']);

    expect($hidden->normalizeValue(' abc ', null))->toBe('abc')
        ->and($phone->normalizeValue(' 0400000000 ', null)->number)->toBe('0400000000');
});

it('trims composite name and address parts during normalization', function (): void {
    $name = new Name([
        'handle' => 'fullName',
        'useMultipleFields' => true,
        'rows' => (new Name(['useMultipleFields' => true]))->getSubFields(),
    ]);
    $address = new Address([
        'handle' => 'billingAddress',
        'rows' => (new Address())->getSubFields(),
    ]);

    $normalizedName = $name->normalizeValue([
        'firstName' => ' Jane ',
        'lastName' => ' Doe ',
    ], null);
    $normalizedAddress = $address->normalizeValue([
        'address1' => ' 123 Main St ',
        'city' => ' Melbourne ',
        'country' => 'AU',
    ], null);

    expect($normalizedName->firstName)->toBe('Jane')
        ->and($normalizedName->lastName)->toBe('Doe')
        ->and($normalizedAddress->address1)->toBe('123 Main St')
        ->and($normalizedAddress->city)->toBe('Melbourne');
});

it('feeds trimmed submission values to integrations', function (): void {
    $field = new SingleLineText(['handle' => 'company']);
    $integrationField = new IntegrationField(['type' => IntegrationField::TYPE_STRING]);
    $value = $field->normalizeValue(' Acme Corp ', null);

    expect(IntegrationHelper::convertValueForIntegration($value, $integrationField))->toBe('Acme Corp');
});
