<?php

declare(strict_types=1);

use verbb\formie\fields\Phone;
use verbb\formie\fields\coercion\PhoneValueCoercer;
use verbb\formie\helpers\IntegrationHelper;
use verbb\formie\models\IntegrationField;
use verbb\formie\fields\values\PhoneFieldValue;
use verbb\formie\helpers\References;
use verbb\formie\references\ReferenceContext;

it('retains submitted number and country in an immutable domain value', function (): void {
    $field = new Phone(['countryDefaultValue' => 'US']);
    $value = $field->normalizeFieldValue(['number' => '0400 000 000', 'country' => 'au']);

    expect($value)->toBeInstanceOf(PhoneFieldValue::class)
        ->and($value->number)->toBe('0400 000 000')
        ->and($value->country)->toBe('AU')
        ->and($value->canonicalNumber)->toBe('+61400000000')
        ->and($value->countryCode)->toBe('+61')
        ->and((string)$value)->toBe('+61400000000')
        ->and($field->normalizeFieldValue($value))->toBe($value)
        ->and($field->serializeValueForClientInput($value))->toBe(['number' => '0400 000 000', 'country' => 'AU'])
        ->and($value->canResolvePath('hasCountryCode'))->toBeFalse()
        ->and($value->canResolvePath('countryName'))->toBeFalse();
    expect(fn() => $value->number = 'changed')->toThrow(LogicException::class);

    $stored = $field->serializeValueForDb($value);
    $field->countryDefaultValue = 'GB';
    expect($stored)->toBe(['number' => '0400 000 000', 'country' => 'AU'])
        ->and($field->getValueAsData($field->normalizeValueFromStorage($stored)))->toBe($value->toArray());
});

it('retains invalid input and explicit region without guessing away either', function (): void {
    $field = new Phone(['countryDefaultValue' => 'US']);
    $value = $field->normalizeFieldValue(['number' => 'not a phone', 'country' => 'NZ']);
    expect($value->canonicalNumber)->toBeNull()
        ->and((string)$value)->toBe('not a phone')
        ->and($field->serializeValueForClientInput($value))->toBe(['number' => 'not a phone', 'country' => 'NZ'])
        ->and($field->isValueEmpty($value, null))->toBeFalse()
        ->and($field->isValueEmpty($field->normalizeFieldValue(['number' => '', 'country' => 'NZ']), null))->toBeTrue();
});

it('decodes Formie 3 encrypted phone parts only from stored content', function (): void {
    $field = new Phone();
    $legacy = ['number' => \verbb\formie\helpers\StringHelper::encenc('0400000000'), 'country' => 'AU', 'hasCountryCode' => true];
    expect($field->normalizeValueFromStorage($legacy)->canonicalNumber)->toBe('+61400000000')
        ->and($field->normalizeValueFromRequest($legacy, null)->number)->toBe($legacy['number']);
});

it('resolves phone references and queries stored numbers without presentation state', function (): void {
    $form = formie()->form()->phoneField('phone')->create();
    $saved = formie()->submission($form)->with(['phone' => ['number' => '0400000000', 'country' => 'AU']])->save();
    $submission = \verbb\formie\elements\Submission::find()->id($saved->id)->status(null)->one();
    $context = ReferenceContext::forSubmission($submission);
    expect($submission->getFieldValue('phone'))->toBeInstanceOf(PhoneFieldValue::class)
        ->and(References::interpolateText('{field:phone}', $context))->toBe('+61400000000')
        ->and(References::resolveValue('{field:phone:country}', $context)->requireValue())->toBe('AU')
        ->and(References::resolveValue('{field:phone:countryName}', $context)->requireValue())->toBe('Australia')
        ->and(References::resolveValue('{field:phone:countryCode}', $context)->requireValue())->toBe('+61')
        ->and(References::resolveValue('{field:phone:canonicalNumber}', $context)->requireValue())->toBe('+61400000000')
        ->and(\verbb\formie\elements\Submission::find()->formId($form->id)->field('phone', '0400000000')->ids())->toBe([$submission->id]);
});

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
