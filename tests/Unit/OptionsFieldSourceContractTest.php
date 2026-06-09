<?php

declare(strict_types=1);

use verbb\formie\base\OptionsField;
use verbb\formie\compatibility\fields\FieldConfigNormalizer;
use verbb\formie\fields\Checkboxes;
use verbb\formie\fields\Dropdown;
use verbb\formie\fields\Radio;
use verbb\formie\helpers\OptionsMode;

function makeTestOptionsField(array $config = []): OptionsField
{
    return new class($config) extends OptionsField {
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

        public function translatedOptionsForTest(): array
        {
            return $this->translatedOptions();
        }
    };
}

function validationInRule(OptionsField $field): ?array
{
    foreach ($field->getElementValidationRules() as $rule) {
        if (is_array($rule) && ($rule[1] ?? null) === 'in') {
            return $rule;
        }
    }

    return null;
}

it('defaults options mode to static for legacy field config', function (): void {
    $config = [
        'options' => [
            ['label' => 'One', 'value' => 'one'],
        ],
    ];

    FieldConfigNormalizer::normalize($config, Dropdown::class);

    expect($config['optionsMode'] ?? null)->toBe(OptionsMode::STATIC)
        ->and(array_key_exists('optionSource', $config))->toBeFalse();
});

it('normalizes invalid options mode values to static', function (): void {
    expect(OptionsMode::normalize(null))->toBe(OptionsMode::STATIC)
        ->and(OptionsMode::normalize('invalid'))->toBe(OptionsMode::STATIC)
        ->and(OptionsMode::normalize(OptionsMode::DYNAMIC))->toBe(OptionsMode::DYNAMIC)
        ->and(OptionsMode::normalize(OptionsMode::TEMPLATE))->toBe(OptionsMode::TEMPLATE)
        ->and(OptionsMode::normalize('source'))->toBe(OptionsMode::STATIC);
});

it('clears option source config when mode is static', function (): void {
    $field = makeTestOptionsField([
        'optionsMode' => OptionsMode::STATIC,
        'optionSource' => [
            'type' => 'predefined',
            'provider' => 'countries',
        ],
        'options' => [
            ['label' => 'One', 'value' => 'one'],
        ],
    ]);

    expect($field->getOptionsMode())->toBe(OptionsMode::STATIC)
        ->and($field->optionSource)->toBeNull();
});

it('retains option source config for dynamic mode', function (): void {
    $field = makeTestOptionsField([
        'optionsMode' => OptionsMode::DYNAMIC,
        'optionSource' => [
            'type' => 'predefined',
            'provider' => 'countries',
            'params' => ['valueKey' => '2-letter'],
        ],
        'options' => [],
    ]);

    expect($field->getOptionsMode())->toBe(OptionsMode::DYNAMIC)
        ->and($field->getOptionSource()?->provider)->toBe('countries')
        ->and($field->getOptionSource()?->params)->toBe(['valueKey' => '2-letter']);
});

it('clears option source config for template mode', function (): void {
    $field = makeTestOptionsField([
        'optionsMode' => OptionsMode::TEMPLATE,
        'optionSource' => [
            'type' => 'predefined',
            'provider' => 'countries',
        ],
        'options' => [],
    ]);

    expect($field->getOptionsMode())->toBe(OptionsMode::TEMPLATE)
        ->and($field->optionSource)->toBeNull();
});

it('demotes non-canonical integration source config when constructing options fields', function (): void {
    $field = new Radio([
        'optionsMode' => OptionsMode::DYNAMIC,
        'optionSource' => [
            'type' => 'remoteIntegration',
            'provider' => 'mailchimp-interests',
            'params' => [
                'integrationId' => 123,
                'collectionId' => 'abc',
                'remoteHandle' => 'interestCategories',
            ],
        ],
    ]);

    expect($field->getOptionsMode())->toBe(OptionsMode::STATIC)
        ->and($field->optionSource)->toBeNull();
});

it('applies strict in validation for static and dynamic modes', function (): void {
    $options = [
        ['label' => 'One', 'value' => 'one'],
        ['label' => 'Two', 'value' => 'two'],
    ];

    $staticField = makeTestOptionsField([
        'handle' => 'testStatic',
        'optionsMode' => OptionsMode::STATIC,
        'options' => $options,
    ]);

    $dynamicField = makeTestOptionsField([
        'handle' => 'testDynamic',
        'optionsMode' => OptionsMode::DYNAMIC,
        'optionSource' => ['type' => 'predefined', 'provider' => 'countries'],
        'options' => $options,
    ]);

    $staticRule = validationInRule($staticField);
    $dynamicRule = validationInRule($dynamicField);

    expect($staticRule['range'] ?? null)->toBe(['one', 'two'])
        ->and($dynamicField->usesStrictOptionValidation())->toBeTrue()
        ->and($dynamicRule)->not->toBeNull();
});

it('does not apply strict in validation for template mode', function (): void {
    $field = makeTestOptionsField([
        'handle' => 'testTemplate',
        'optionsMode' => OptionsMode::TEMPLATE,
        'options' => [],
    ]);

    expect($field->usesStrictOptionValidation())->toBeFalse()
        ->and(validationInRule($field))->toBeNull();
});

it('demotes legacy twig dynamic configs to static', function (): void {
    $config = [
        'optionsMode' => OptionsMode::DYNAMIC,
        'optionSource' => [
            'type' => 'dynamic',
            'params' => ['template' => '[{"label":"A","value":"a"}]'],
        ],
    ];

    FieldConfigNormalizer::normalize($config, Dropdown::class);

    expect($config['optionsMode'])->toBe(OptionsMode::STATIC)
        ->and(array_key_exists('optionSource', $config))->toBeFalse();
});

it('rejects element option source when constructing options fields', function (): void {
    $field = new Dropdown([
        'optionsMode' => OptionsMode::DYNAMIC,
        'optionSource' => [
            'type' => 'element',
            'provider' => 'entries',
        ],
    ]);

    expect($field->getOptionsMode())->toBe(OptionsMode::STATIC)
        ->and($field->optionSource)->toBeNull();
})->skip(fn (): bool => !class_exists(\Craft::class), 'Requires Craft bootstrap');

it('demotes element option sources on options field types', function (): void {
    foreach ([Dropdown::class, Radio::class, Checkboxes::class] as $fieldClass) {
        $config = [
            'optionsMode' => OptionsMode::DYNAMIC,
            'optionSource' => [
                'type' => 'element',
                'provider' => 'entries',
            ],
        ];

        FieldConfigNormalizer::normalize($config, $fieldClass);

        expect($config['optionsMode'])->toBe(OptionsMode::STATIC)
            ->and(array_key_exists('optionSource', $config))->toBeFalse();
    }
});

it('retains predefined option source config for options field types', function (): void {
    foreach ([Dropdown::class, Radio::class, Checkboxes::class] as $fieldClass) {
        $config = [
            'optionsMode' => OptionsMode::DYNAMIC,
            'optionSource' => [
                'type' => 'predefined',
                'provider' => 'countries',
            ],
        ];

        FieldConfigNormalizer::normalize($config, $fieldClass);

        expect($config['optionsMode'])->toBe(OptionsMode::DYNAMIC)
            ->and($config['optionSource']['provider'] ?? null)->toBe('countries');
    }
});

it('persists value and label for dynamic mode submissions', function (): void {
    $field = makeTestOptionsField([
        'optionsMode' => OptionsMode::DYNAMIC,
        'optionSource' => ['type' => 'predefined', 'provider' => 'countries'],
        'options' => [
            ['label' => 'Australia', 'value' => 'AU'],
        ],
    ]);

    $normalized = $field->normalizeValue([
        'value' => 'AU',
        'label' => 'Australia',
    ]);

    expect($normalized)->toBeInstanceOf(\verbb\formie\fields\values\SingleOptionFieldValue::class)
        ->and($normalized->value)->toBe('AU')
        ->and($normalized->getDisplayLabel())->toBe('Australia')
        ->and($field->serializeValue($normalized, null))->toBe([
            'value' => 'AU',
            'label' => 'Australia',
        ]);
});

it('trims options from snapshot settings for dynamic modes', function (): void {
    $settings = [
        'optionsMode' => OptionsMode::DYNAMIC,
        'optionSource' => ['type' => 'predefined', 'provider' => 'countries'],
        'options' => [['label' => 'Cached', 'value' => 'cached']],
    ];

    $normalized = OptionsField::normalizeSnapshotFieldSettings($settings);

    expect($normalized)->not->toHaveKey('options')
        ->and($normalized['optionSource']['provider'] ?? null)->toBe('countries');
});

it('trims options from snapshot settings for template mode', function (): void {
    $settings = [
        'optionsMode' => OptionsMode::TEMPLATE,
        'options' => [['label' => 'Template row', 'value' => 'template-row']],
    ];

    $normalized = OptionsField::normalizeSnapshotFieldSettings($settings);

    expect($normalized)->not->toHaveKey('options')
        ->and($normalized['optionsMode'])->toBe(OptionsMode::TEMPLATE);
});

it('accepts submitted template values without a configured option list', function (): void {
    $field = makeTestOptionsField([
        'handle' => 'templateField',
        'optionsMode' => OptionsMode::TEMPLATE,
        'options' => [],
    ]);

    $normalized = $field->normalizeValue([
        'value' => 'developer-owned',
        'label' => 'Developer Owned',
    ]);

    expect($normalized)->toBeInstanceOf(\verbb\formie\fields\values\SingleOptionFieldValue::class)
        ->and($normalized->value)->toBe('developer-owned')
        ->and($normalized->getDisplayLabel())->toBe('Developer Owned')
        ->and($normalized->valid)->toBeTrue();
});

it('exposes resolved dynamic options to form builder settings', function (): void {
    $field = new Dropdown([
        'optionsMode' => OptionsMode::DYNAMIC,
        'optionSource' => [
            'type' => 'predefined',
            'provider' => 'countries',
            'params' => [
                'labelKey' => 'name',
                'valueKey' => '2-letter',
            ],
        ],
        'options' => [],
    ]);

    $settings = $field->getFormBuilderSettings();

    expect($settings['options'])->toBe([])
        ->and($settings['_previewOptions'])->not->toBeEmpty()
        ->and($settings['_previewOptions'][0]['label'] ?? null)->not->toBe('')
        ->and($settings['_previewOptions'][0]['value'] ?? null)->toHaveLength(2);
})->skip(fn (): bool => !class_exists(\Craft::class) || !\Craft::$app || !\verbb\formie\Formie::$plugin, 'Requires Craft bootstrap');

it('translates resolved dynamic options for cp submission inputs', function (): void {
    $field = makeTestOptionsField([
        'optionsMode' => OptionsMode::DYNAMIC,
        'optionSource' => [
            'type' => 'predefined',
            'provider' => 'countries',
            'params' => [
                'labelKey' => 'name',
                'valueKey' => '2-letter',
            ],
        ],
        'options' => [],
    ]);

    $options = $field->translatedOptionsForTest();

    expect($options)->not->toBeEmpty()
        ->and($options[0]['label'] ?? null)->not->toBe('')
        ->and($options[0]['value'] ?? null)->toHaveLength(2);
})->skip(fn (): bool => !class_exists(\Craft::class) || !\Craft::$app || !\verbb\formie\Formie::$plugin, 'Requires Craft bootstrap');

it('marks unknown dynamic values invalid while retaining submitted labels for display', function (): void {
    $field = makeTestOptionsField([
        'handle' => 'dynamicField',
        'optionsMode' => OptionsMode::DYNAMIC,
        'optionSource' => ['type' => 'predefined', 'provider' => 'countries'],
        'options' => [
            ['label' => 'Known', 'value' => 'known'],
        ],
    ]);

    $normalized = $field->normalizeValue([
        'value' => 'unknown',
        'label' => 'Unknown Label',
    ]);

    expect($normalized)->toBeInstanceOf(\verbb\formie\fields\values\SingleOptionFieldValue::class)
        ->and($normalized->value)->toBe('unknown')
        ->and($normalized->getDisplayLabel())->toBe('Unknown Label')
        ->and($normalized->valid)->toBeFalse();
});

it('exposes resolved options through getFieldOptions in dynamic mode', function (): void {
    $field = new class([
        'optionsMode' => OptionsMode::DYNAMIC,
        'options' => [],
    ]) extends OptionsField {
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

        public function getResolvedOptions(): array
        {
            return [
                ['label' => 'Australia', 'value' => 'AU'],
                ['label' => 'New Zealand', 'value' => 'NZ'],
            ];
        }
    };

    expect($field->getFieldOptions())->toHaveCount(2)
        ->and($field->getFieldOptions()[0]['value'])->toBe('AU')
        ->and($field->getFieldOptions()[1]['label'])->toBe('New Zealand');
});

it('resolves dynamic predefined options for front-end field options', function (): void {
    $field = new Dropdown([
        'optionsMode' => OptionsMode::DYNAMIC,
        'optionSource' => [
            'type' => 'predefined',
            'provider' => 'countries',
            'params' => [
                'labelKey' => 'name',
                'valueKey' => '2-letter',
            ],
        ],
    ]);

    $options = $field->getFieldOptions();

    expect($options)->not->toBeEmpty()
        ->and($options[0]['label'] ?? null)->not->toBe('')
        ->and($options[0]['value'] ?? null)->toHaveLength(2);
})->skip(fn (): bool => !class_exists(\Craft::class), 'Requires Craft bootstrap');
