<?php

declare(strict_types=1);

use verbb\formie\fields\SingleLineText;
use verbb\formie\Formie;
use verbb\formie\helpers\ValidationMessagesHelper;

it('resolves default validation messages with field context', function (): void {
    $field = new SingleLineText([
        'label' => 'Username',
        'handle' => 'username',
    ]);

    expect($field->getValidationMessage(ValidationMessagesHelper::KEY_REQUIRED))
        ->toBe('Username cannot be blank.')
        ->and($field->getValidationMessage(ValidationMessagesHelper::KEY_UNIQUE))
        ->toBe('“Username” must be unique.')
        ->and($field->getValidationMessage(ValidationMessagesHelper::KEY_MAX_CHARACTERS, ['max' => 10, 'limit' => 10]))
        ->toBe('Username must be no greater than 10 characters.');
});

it('uses validation message overrides with placeholder interpolation', function (): void {
    $field = new SingleLineText([
        'label' => 'Email',
        'handle' => 'email',
        'validationMessages' => [
            'required' => '{label} is mandatory.',
            'unique' => 'Pick another {label}.',
            'maxCharacters' => 'Keep {label} under {max} characters.',
        ],
    ]);

    expect($field->getValidationMessage(ValidationMessagesHelper::KEY_REQUIRED))
        ->toBe('Email is mandatory.')
        ->and($field->getValidationMessage(ValidationMessagesHelper::KEY_UNIQUE))
        ->toBe('Pick another Email.')
        ->and($field->getValidationMessage(ValidationMessagesHelper::KEY_MAX_CHARACTERS, ['max' => 5, 'limit' => 5]))
        ->toBe('Keep Email under 5 characters.')
        ->and($field->getValidationMessageClientAttribute(ValidationMessagesHelper::KEY_REQUIRED))
        ->toBe('Email is mandatory.')
        ->and($field->getValidationMessageClientAttribute(ValidationMessagesHelper::KEY_UNIQUE))
        ->toBe('Pick another Email.');
});

it('resolves email blocked domain messages with domain token', function (): void {
    $field = new \verbb\formie\fields\Email([
        'label' => 'Work Email',
        'handle' => 'workEmail',
        'validationMessages' => [
            'blockedDomain' => 'Please use a company email, not {domain}.',
        ],
    ]);

    expect($field->getValidationMessage(ValidationMessagesHelper::KEY_BLOCKED_DOMAIN, [
        'domain' => 'gmail.com',
    ]))->toBe('Please use a company email, not gmail.com.');
});

it('migrates legacy errorMessage into required validation messages', function (): void {
    $field = new SingleLineText([
        'label' => 'Name',
        'handle' => 'name',
        'errorMessage' => 'Please enter your {label}.',
    ]);

    expect(ValidationMessagesHelper::override($field, ValidationMessagesHelper::KEY_REQUIRED))
        ->toBe('Please enter your {label}.')
        ->and($field->getValidationMessage(ValidationMessagesHelper::KEY_REQUIRED))
        ->toBe('Please enter your Name.');
});

it('resolves nested field validation message overrides from the parent field', function (): void {
    $parent = new \verbb\formie\fields\Date([
        'label' => 'Birthday',
        'handle' => 'birthday',
        'validationMessages' => [
            'required' => 'Please provide your {label}.',
            'invalid' => 'Fix the {label} part.',
        ],
    ]);

    $child = new \verbb\formie\fields\subfields\DateDayNumber([
        'label' => 'Day',
        'handle' => 'day',
        'validationMessages' => [
            'required' => 'Child override should not be used.',
        ],
        'errorMessage' => 'Legacy child message.',
    ]);

    $reflection = new ReflectionClass($child);
    $method = $reflection->getMethod('applyParentFieldContext');
    $method->setAccessible(true);
    $method->invoke($child, $parent);

    expect($child->getValidationMessage(ValidationMessagesHelper::KEY_REQUIRED))
        ->toBe('Please provide your Day.')
        ->and($child->getValidationMessage(ValidationMessagesHelper::KEY_INVALID))
        ->toBe('Fix the Day part.')
        ->and(ValidationMessagesHelper::override($child, ValidationMessagesHelper::KEY_REQUIRED))
        ->toBe('Please provide your {label}.');
});

it('uses plugin validation message defaults when a field has no override', function (): void {
    $previousDefaults = Formie::$plugin->getSettings()->validationMessageDefaults;
    Formie::$plugin->getSettings()->validationMessageDefaults = [
        'required' => 'Please complete {label}.',
        'unique' => 'The {label} value is already taken.',
    ];

    try {
        $field = new SingleLineText([
            'label' => 'Username',
            'handle' => 'username',
        ]);

        expect($field->getValidationMessage(ValidationMessagesHelper::KEY_REQUIRED))
            ->toBe('Please complete Username.')
            ->and($field->getValidationMessage(ValidationMessagesHelper::KEY_UNIQUE))
            ->toBe('The Username value is already taken.')
            ->and($field->getValidationMessageClientAttribute(ValidationMessagesHelper::KEY_REQUIRED))
            ->toBe('Please complete Username.');
    } finally {
        Formie::$plugin->getSettings()->validationMessageDefaults = $previousDefaults;
    }
});

it('prefers field validation message overrides over plugin defaults', function (): void {
    $previousDefaults = Formie::$plugin->getSettings()->validationMessageDefaults;
    Formie::$plugin->getSettings()->validationMessageDefaults = [
        'required' => 'Plugin default for {label}.',
    ];

    try {
        $field = new SingleLineText([
            'label' => 'Email',
            'handle' => 'email',
            'validationMessages' => [
                'required' => 'Field override for {label}.',
            ],
        ]);

        expect($field->getValidationMessage(ValidationMessagesHelper::KEY_REQUIRED))
            ->toBe('Field override for Email.');
    } finally {
        Formie::$plugin->getSettings()->validationMessageDefaults = $previousDefaults;
    }
});

it('normalizes plugin validation message defaults for storage', function (): void {
    expect(ValidationMessagesHelper::normalizeDefaultsForStorage([
        'required' => 'Please enter {name}.',
        'unique' => '',
        'invalid' => '{label} is invalid.',
        'unknown' => 'Ignore me.',
    ]))->toBe([
        'required' => 'Please enter {label}.',
    ]);
});

it('seeds front-end translations from canonical validation templates', function (): void {
    $strings = ValidationMessagesHelper::frontendTranslationStringList();

    expect($strings)
        ->toContain('{label} is not a valid email address.')
        ->toContain('{label} cannot be blank.')
        ->toContain('Please enter a valid email address.');
});
