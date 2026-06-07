<?php

declare(strict_types=1);

use verbb\formie\base\OptionsField;
use verbb\formie\fields\Dropdown;

it('excludes hidden options from front-end field options', function (): void {
    $field = new class(['options' => [
        ['label' => 'Active', 'value' => 'active'],
        ['label' => 'Retired', 'value' => 'retired', 'availability' => 'hidden'],
    ]]) extends OptionsField {
        protected function optionsSettingLabel(): string
        {
            return 'Options';
        }

        public static function displayName(): string
        {
            return 'Test Options';
        }

        public static function getSvgIconPath(): string
        {
            return '';
        }
    };

    expect($field->getFieldOptions())->toHaveCount(1)
        ->and($field->getFieldOptions()[0]['value'])->toBe('active');
});

it('maps legacy disabled option rows to hidden availability', function (): void {
    $field = new class(['options' => [
        ['label' => 'Active', 'value' => 'active'],
        ['label' => 'Retired', 'value' => 'retired', 'disabled' => true],
    ]]) extends OptionsField {
        protected function optionsSettingLabel(): string
        {
            return 'Options';
        }

        public static function displayName(): string
        {
            return 'Test Options';
        }

        public static function getSvgIconPath(): string
        {
            return '';
        }
    };

    expect($field->getFieldOptions())->toHaveCount(1)
        ->and(OptionsField::resolveOptionAvailability(['disabled' => true]))->toBe('hidden');
});

it('includes disabled options in front-end field options', function (): void {
    $field = new class(['options' => [
        ['label' => 'Active', 'value' => 'active'],
        ['label' => 'Unavailable', 'value' => 'unavailable', 'availability' => 'disabled'],
    ]]) extends OptionsField {
        protected function optionsSettingLabel(): string
        {
            return 'Options';
        }

        public static function displayName(): string
        {
            return 'Test Options';
        }

        public static function getSvgIconPath(): string
        {
            return '';
        }
    };

    expect($field->getFieldOptions())->toHaveCount(2)
        ->and($field->getFieldOptions()[1]['disabled'] ?? false)->toBeTrue()
        ->and(OptionsField::isOptionFrontEndDisabled($field->getFieldOptions()[1]))->toBeTrue();
});

it('resolves disabled and hidden availability states', function (): void {
    expect(OptionsField::resolveOptionAvailability(['availability' => 'hidden']))->toBe('hidden')
        ->and(OptionsField::resolveOptionAvailability(['availability' => 'disabled']))->toBe('disabled')
        ->and(OptionsField::resolveOptionAvailability(['label' => 'Visible']))->toBeNull()
        ->and(OptionsField::isOptionFrontEndDisabled(['availability' => 'disabled']))->toBeTrue()
        ->and(OptionsField::isOptionHidden(['availability' => 'hidden']))->toBeTrue();
});

it('prepends dropdown placeholder before visible options', function (): void {
    $field = new Dropdown([
        'placeholder' => 'Choose one',
        'options' => [
            ['label' => 'One', 'value' => 'one'],
            ['label' => 'Hidden', 'value' => 'hidden', 'availability' => 'hidden'],
        ],
    ]);

    $options = $field->getFieldOptions();

    expect($options)->toHaveCount(2)
        ->and($options[0]['value'])->toBe('')
        ->and($options[1]['value'])->toBe('one');
});
