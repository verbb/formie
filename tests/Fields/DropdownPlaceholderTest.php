<?php

declare(strict_types=1);

use Tests\fixtures\OptionSourceProviders\TestOptionsProvider;
use verbb\formie\elements\Form;
use verbb\formie\events\RegisterOptionSourceProvidersEvent;
use verbb\formie\events\RegisterOptionSourceResolversEvent;
use verbb\formie\fields\Dropdown;
use verbb\formie\Formie;
use verbb\formie\helpers\FormSerializer;
use verbb\formie\models\OptionSource;
use verbb\formie\options\OptionList;
use verbb\formie\options\OptionSourceContext;
use verbb\formie\options\OptionSourceFieldInterface;
use verbb\formie\options\OptionSourceResolverInterface;
use verbb\formie\options\OptionSourceValidationMode;
use verbb\formie\services\OptionSources;

it('leaves legacy empty options and defaults unchanged through editing and saves', function (array $extra, string $expected): void {
    $config = array_merge([
        'placeholder' => null,
        'options' => [
            ['label' => 'Pick a country', 'value' => '', 'default' => false],
            ['label' => 'Zero', 'value' => '0', 'default' => true],
            ['label' => 'Australia', 'value' => 'AU', 'default' => false],
        ],
    ], $extra);
    $form = formie()->form()->dropdownField('country', $config)->create();

    for ($save = 0; $save < 2; $save++) {
        Formie::$plugin->getFields()->resetFieldRegistryCache();
        $form = Form::find()->id($form->id)->one();
        $field = $form->getFieldByHandle('country');
        $settings = json_decode(json_encode($field->getFormBuilderSettings(), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

        expect($settings['showPlaceholder'])->toBeFalse()
            ->and($settings['placeholder'])->toBe('Select an option')
            ->and(array_column($settings['options'], 'value'))->toBe(['', '0', 'AU'])
            ->and(array_column($settings['options'], 'label'))->toBe(array_column($config['options'], 'label'))
            ->and($field->getDefaultValue()?->value ?? '')->toBe($expected)
            ->and(array_column($field->getFieldOptions(), 'value'))->toBe(['', '0', 'AU']);

        $document = new DOMDocument();
        @$document->loadHTML((string)Formie::$plugin->getRendering()->renderField($form, 'country'));
        $xpath = new DOMXPath($document);
        expect($xpath->query('//option[@value=""]')->length)->toBe(1)
            ->and($xpath->query('//option[@value=""]')->item(0)->textContent)->toBe($config['options'][0]['label'])
            ->and($xpath->query('//option[@selected]')->item(0)->getAttribute('value'))->toBe($expected);

        $pages = json_decode(json_encode($form->getFormLayout()->getFormBuilderConfig(), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        $pages[0]['rows'][0]['fields'] = [$settings];
        $form->getFormLayout()->setPages((new FormSerializer())->hydrateBuilder($pages, $form));
        expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
    }
})->with([
    'legacy label and zero default' => [[], '0'],
    'legacy blank placeholder' => [['placeholder' => ''], '0'],
    'explicit default value wins' => [['defaultValue' => 'AU'], 'AU'],
    'legacy empty default wins' => [['options' => [
        ['label' => 'None', 'value' => '', 'default' => true],
        ['label' => 'Zero', 'value' => '0', 'default' => true],
        ['label' => 'Australia', 'value' => 'AU'],
    ]], ''],
]);

it('preserves a legacy explicitly configured placeholder', function (): void {
    $field = Formie::$plugin->getFields()->createField([
        'type' => Dropdown::class,
        'settings' => ['placeholder' => 'Legacy prompt', 'options' => [['label' => 'One', 'value' => '1']]],
    ]);

    expect($field->showPlaceholder)->toBeTrue()
        ->and($field->getFieldOptions()[0])->toBe(['label' => 'Legacy prompt', 'value' => '']);
});

it('keeps an explicitly blank placeholder through field hydration', function (): void {
    $field = Formie::$plugin->getFields()->createField([
        'type' => Dropdown::class,
        'settings' => ['showPlaceholder' => true, 'placeholder' => '', 'options' => []],
    ]);

    expect($field->showPlaceholder)->toBeTrue()
        ->and($field->getFormBuilderSettings()['placeholder'])->toBe('')
        ->and($field->getFieldOptions()[0])->toBe(['label' => '', 'value' => '']);
});

it('applies the placeholder after resolving every dropdown option source', function (string $source, bool $multi, bool $show): void {
    $previous = Formie::$plugin->getOptionSources();
    $service = new OptionSources();
    $service->on(OptionSources::EVENT_REGISTER_OPTION_SOURCE_PROVIDERS, static function (RegisterOptionSourceProvidersEvent $event): void {
        $event->providers[] = TestOptionsProvider::class;
    });
    // Keep remote integrations deterministic while exercising the real source-resolution path.
    $service->on(OptionSources::EVENT_REGISTER_OPTION_SOURCE_RESOLVERS, static function (RegisterOptionSourceResolversEvent $event): void {
        array_unshift($event->resolvers, new class implements OptionSourceResolverInterface {
            public function supports(OptionSource $source): bool { return $source->type === 'integration'; }
            public function resolve(OptionSourceFieldInterface $field, OptionSourceContext $context): OptionList
            {
                return OptionList::fromRows([
                    ['label' => 'Remote prompt', 'value' => ''],
                    ['label' => 'Remote answer', 'value' => 'remote'],
                ]);
            }
            public function validationMode(OptionSource $source): string { return OptionSourceValidationMode::STRICT; }
        });
    });
    Formie::$plugin->set('optionSources', $service);

    try {
        $config = [
            'optionsMode' => in_array($source, ['static', 'template'], true) ? $source : 'dynamic',
            'options' => [],
            'placeholder' => 'Choose an answer',
            'showPlaceholder' => $show,
            'multi' => $multi,
            'useSearchable' => true,
        ];
        if (!in_array($source, ['static', 'template'], true)) {
            $config['optionSource'] = [
                'type' => $source,
                'provider' => match ($source) {
                    'predefined' => 'countries',
                    'provider' => 'test-options',
                    default => 'mailchimp-interests',
                },
            ];
        }
        $field = new Dropdown($config);
        if (in_array($source, ['static', 'template'], true)) {
            // Template mode supplies its rows after field construction.
            $field->options = [['label' => 'Source prompt', 'value' => ''], ['label' => 'Zero', 'value' => '0']];
        }
        $values = array_column($field->getFieldOptions(), 'value');
        expect(count(array_filter($values, static fn($value): bool => $value === '')))->toBe((in_array($source, ['static', 'integration', 'template'], true) ? 1 : 0) + ($show && !$multi ? 1 : 0))
            ->and(array_values(array_filter($values, static fn($value): bool => $value !== '')))->not->toBeEmpty();
        $input = $field->getClientRenderedInput();
        expect($input['placeholder'])->toBe($show ? 'Choose an answer' : null)
            ->and(array_column($input['options'], 'value'))->toBe($values);
        $settings = $field->getFormBuilderSettings();
        expect($settings['placeholder'])->toBe('Choose an answer')
            ->and($settings['showPlaceholder'])->toBe($show);
    } finally {
        Formie::$plugin->set('optionSources', $previous);
    }
})->with(['static', 'predefined', 'provider', 'integration', 'template'])->with([false, true])->with([false, true]);

it('adds the default prompt without replacing an authored template option', function (): void {
    $field = new Dropdown(['optionsMode' => 'template', 'showPlaceholder' => true]);
    $field->options = [['label' => 'No preference', 'value' => ''], ['label' => 'One', 'value' => '1']];

    expect($field->getPlaceholderLabel())->toBe('Select an option')
        ->and(array_column($field->getFieldOptions(), 'label'))->toBe(['Select an option', 'No preference', 'One'])
        ->and(array_column($field->getFormBuilderSettings()['_previewOptions'], 'label'))->toBe(['No preference', 'One']);
});

it('authors placeholders separately from answer rows for new dropdowns', function (): void {
    $field = new Dropdown();
    $settings = $field->getFieldTypeConfig(false)['newField'];
    $schema = array_column($field->defineFormBuilderSettingsSchema(), null, 'name');

    expect($settings['options'])->toBe([])
        ->and($settings['showPlaceholder'])->toBeTrue()
        ->and($settings['placeholder'])->toBe('Select an option')
        ->and($schema['showPlaceholder'])->not->toHaveKey('if')
        ->and($schema['placeholder']['if'])->toBe('showPlaceholder')
        ->and(Dropdown::translatableProperties())->toContain('placeholder');
});

it('preserves placeholder settings when switching option sources and saving', function (): void {
    $form = formie()->form()->dropdownField('country', [
        'showPlaceholder' => true,
        'placeholder' => 'Choose your destination',
        'options' => [['label' => 'Australia', 'value' => 'AU']],
        'defaultValue' => 'AU',
    ])->create();

    foreach (['dynamic', 'template', 'static'] as $mode) {
        Formie::$plugin->getFields()->resetFieldRegistryCache();
        $form = Form::find()->id($form->id)->one();
        $settings = $form->getFieldByHandle('country')->getFormBuilderSettings();
        $settings['optionsMode'] = $mode;
        $settings['optionSource'] = $mode === 'dynamic' ? [
            'type' => 'predefined', 'provider' => 'countries', 'params' => ['valueKey' => '2-letter'],
        ] : null;
        $settings['showPlaceholder'] = $mode !== 'template';
        $settings['options'] = [['label' => 'Australia', 'value' => 'AU']];
        $pages = json_decode(json_encode($form->getFormLayout()->getFormBuilderConfig(), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        $pages[0]['rows'][0]['fields'] = [$settings];
        $form->getFormLayout()->setPages((new FormSerializer())->hydrateBuilder($pages, $form));
        expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

        Formie::$plugin->getFields()->resetFieldRegistryCache();
        $reloaded = Form::find()->id($form->id)->one()->getFieldByHandle('country');
        expect($reloaded->placeholder)->toBe('Choose your destination')
            ->and($reloaded->showPlaceholder)->toBe($mode !== 'template')
            ->and($reloaded->defaultValue)->toBe('AU')
            ->and($reloaded->getOptionsMode())->toBe($mode);
    }
});

it('lets existing source-backed dropdowns opt in without changing their defaults', function (string $mode, mixed $placeholder): void {
    $config = [
        'type' => Dropdown::class,
        'settings' => [
            'optionsMode' => $mode,
            'placeholder' => $placeholder,
            'options' => [['label' => 'Australia', 'value' => 'AU']],
            'defaultValue' => 'AU',
            'optionSource' => $mode === 'dynamic' ? ['type' => 'predefined', 'provider' => 'countries', 'params' => ['valueKey' => '2-letter']] : null,
        ],
    ];
    $field = Formie::$plugin->getFields()->createField($config);
    $settings = $field->getFormBuilderSettings();
    expect($field->showPlaceholder)->toBeFalse()
        ->and(array_column($field->getFieldOptions(), 'value'))->not->toContain('')
        ->and($settings['placeholder'])->toBe('Select an option');

    $config['settings'] = array_merge($field->getSettings(), [
        'showPlaceholder' => true,
        'placeholder' => $settings['placeholder'],
    ]);
    $field = Formie::$plugin->getFields()->createField($config);
    expect($field->getFieldOptions()[0])->toBe(['label' => 'Select an option', 'value' => ''])
        ->and($field->defaultValue)->toBe('AU');
})->with(['static', 'dynamic', 'template'])->with([null, '']);
