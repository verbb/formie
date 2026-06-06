<?php

declare(strict_types=1);

use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\ValidationMessagesHelper;

it('resolves default validation messages with field context', function (): void {
    $field = new SingleLineText([
        'label' => 'Username',
        'handle' => 'username',
    ]);

    expect($field->getValidationMessage(ValidationMessagesHelper::KEY_REQUIRED))
        ->toBe('This field is required.')
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
