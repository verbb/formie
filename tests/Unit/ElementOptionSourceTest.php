<?php

declare(strict_types=1);

use verbb\formie\fields\Categories;
use verbb\formie\fields\Dropdown;
use verbb\formie\fields\Entries;
use verbb\formie\fields\Recipients;
use verbb\formie\fields\Tags;
use verbb\formie\fields\Users;
use verbb\formie\helpers\OptionsMode;
use verbb\formie\base\ElementField;
use verbb\formie\options\ElementOptionSourceHelper;
use verbb\formie\options\resolvers\ElementOptionSourceResolver;
use verbb\formie\models\OptionSource;

class ElementOptionSourceTestField extends ElementField
{
    public static function displayName(): string
    {
        return 'Custom Elements';
    }

    public static function elementType(): string
    {
        return stdClass::class;
    }

    protected static function defineOptionSource(): ?array
    {
        return [
            'handle' => 'custom-elements',
        ];
    }
}

it('normalizes field-owned option source definitions', function (): void {
    expect(ElementOptionSourceTestField::getOptionSourceDefinition())->toBe([
        'handle' => 'custom-elements',
        'label' => 'Custom Elements',
    ]);
});

it('maps element field classes back to provider slugs', function (): void {
    expect(ElementOptionSourceHelper::getProviderForFieldClass(Entries::class))->toBe('entries')
        ->and(ElementOptionSourceHelper::getProviderForFieldClass(Categories::class))->toBe('categories')
        ->and(ElementOptionSourceHelper::getProviderForFieldClass(Tags::class))->toBe('tags');

    if (\Craft::$app->getEdition() !== \Craft::Solo) {
        expect(ElementOptionSourceHelper::getProviderForFieldClass(Users::class))->toBe('users');
    }

    expect(ElementOptionSourceHelper::getProviderForFieldClass(Dropdown::class))->toBeNull();
})->skip(fn (): bool => !class_exists(\Craft::class) || !\verbb\formie\Formie::$plugin, 'Requires Formie field registry');

it('lets element fields define their option source providers', function (): void {
    expect(Entries::getOptionSourceDefinition()['handle'])->toBe('entries')
        ->and(Categories::getOptionSourceDefinition()['handle'])->toBe('categories')
        ->and(Tags::getOptionSourceDefinition()['handle'])->toBe('tags')
        ->and(Users::getOptionSourceDefinition()['handle'])->toBe('users')
        ->and(is_subclass_of(Dropdown::class, ElementField::class))->toBeFalse();
})->skip(fn (): bool => !class_exists(\Craft::class) || !\Craft::$app, 'Requires Craft bootstrap');

it('builds option source params from element field settings', function (): void {
    $field = new Entries([
        'sources' => ['section:blog'],
        'labelSource' => 'title',
        'orderBy' => 'title ASC',
        'limitOptions' => '25',
    ]);

    expect(ElementOptionSourceHelper::buildParamsFromElementField($field))->toBe([
        'labelSource' => 'title',
        'orderBy' => 'title ASC',
        'limitOptions' => '25',
        'sources' => ['section:blog'],
    ]);
})->skip(fn (): bool => !class_exists(\Craft::class), 'Requires Craft bootstrap');

it('describes element fields as option sources', function (): void {
    $field = new Categories([
        'source' => 'group:news',
        'labelSource' => 'title',
        'orderBy' => 'title DESC',
    ]);

    $source = $field->toOptionSource();

    expect($source)->not->toBeNull()
        ->and($source->type)->toBe('element')
        ->and($source->provider)->toBe('categories')
        ->and($source->params['source'])->toBe('group:news')
        ->and($source->params['labelSource'])->toBe('title')
        ->and($source->params['orderBy'])->toBe('title DESC');
})->skip(fn (): bool => !class_exists(\Craft::class) || !\verbb\formie\Formie::$plugin, 'Requires Formie field registry');

it('resolves options from a real element field instance', function (): void {
    $field = new Entries([
        'sources' => '*',
        'labelSource' => 'title',
        'orderBy' => 'title ASC',
    ]);

    $result = ElementOptionSourceHelper::resolveFromElementField($field);

    expect($result->error)->toBeNull()
        ->and($result->items)->toBeArray();
})->skip(fn (): bool => !class_exists(\Craft::class), 'Requires Craft bootstrap');

it('keeps recipient obfuscation separate from resolved options', function (): void {
    $field = new Recipients([
        'options' => [
            ['label' => 'Sales', 'value' => 'sales@example.com'],
            ['label' => 'Support', 'value' => 'support@example.com'],
        ],
    ]);

    $fieldOptions = $field->getFieldOptions();

    expect($field->getResolvedOptions())->toBe($field->options())
        ->and($field->getResolvedOptions()[0]['value'])->toBe('sales@example.com')
        ->and($fieldOptions[0]['value'])->toStartWith('base64:')
        ->and($fieldOptions[1]['value'])->toStartWith('base64:')
        ->and($field->getRealValue($fieldOptions[0]['value']))->toBe('sales@example.com')
        ->and($field->getRealValue($fieldOptions[1]['value']))->toBe('support@example.com');
})->skip(fn (): bool => !class_exists(\Craft::class) || !\Craft::$app || !\verbb\formie\Formie::$plugin, 'Requires Craft bootstrap');

it('decodes recipient option tokens independently of resolved option order', function (): void {
    $field = new Recipients([
        'displayType' => 'dropdown',
        'options' => [
            ['label' => 'Sales', 'value' => 'sales@example.com'],
            ['label' => 'Support', 'value' => 'support@example.com'],
        ],
    ]);
    $salesToken = $field->getFieldOptions()[0]['value'];

    $reorderedField = new Recipients([
        'displayType' => 'dropdown',
        'options' => [
            ['label' => 'Support', 'value' => 'support@example.com'],
            ['label' => 'Sales', 'value' => 'sales@example.com'],
        ],
    ]);

    expect($reorderedField->getRealValue($salesToken))->toBe('sales@example.com');
})->skip(fn (): bool => !class_exists(\Craft::class) || !\Craft::$app || !\verbb\formie\Formie::$plugin, 'Requires Craft bootstrap');

it('marks unknown visible recipient option values invalid', function (): void {
    $field = new Recipients([
        'handle' => 'recipient',
        'displayType' => 'dropdown',
        'options' => [
            ['label' => 'Sales', 'value' => 'sales@example.com'],
        ],
    ]);

    $value = $field->normalizeValue('intruder@example.com', null);
    $validationRules = $field->getElementValidationRules();

    expect($value)->toBeInstanceOf(\verbb\formie\fields\values\RecipientsFieldValue::class)
        ->and($value->valid())->toBeFalse()
        ->and($validationRules)->toContain([0 => 'recipient', 1 => 'validateVisibleRecipientOptions', 'skipOnEmpty' => false]);
})->skip(fn (): bool => !class_exists(\Craft::class) || !\Craft::$app || !\verbb\formie\Formie::$plugin, 'Requires Craft bootstrap');

it('maps element provider slugs to field classes', function (): void {
    expect(ElementOptionSourceHelper::getProviderFieldClass('entries'))->toBe(Entries::class)
        ->and(ElementOptionSourceHelper::getProviderFieldClass('categories'))->toBe(Categories::class)
        ->and(ElementOptionSourceHelper::getProviderFieldClass('tags'))->toBe(Tags::class);

    if (\Craft::$app->getEdition() !== \Craft::Solo) {
        expect(ElementOptionSourceHelper::getProviderFieldClass('users'))->toBe(Users::class);
    }

    expect(ElementOptionSourceHelper::getProviderFieldClass('unknown'))->toBeNull();
})->skip(fn (): bool => !class_exists(\Craft::class) || !\verbb\formie\Formie::$plugin, 'Requires Formie field registry');

it('builds multi-source element field config from option source params', function (): void {
    $config = ElementOptionSourceHelper::buildFieldConfig('entries', [
        'sources' => ['section:blog'],
        'labelSource' => 'title',
        'orderBy' => 'title ASC',
        'limitOptions' => '50',
    ]);

    expect($config['sources'])->toBe(['section:blog'])
        ->and($config['labelSource'])->toBe('title')
        ->and($config['orderBy'])->toBe('title ASC')
        ->and($config['limitOptions'])->toBe('50');
})->skip(fn (): bool => !class_exists(\Craft::class) || !\verbb\formie\Formie::$plugin, 'Requires Formie field registry');

it('builds single-source element field config from option source params', function (): void {
    $config = ElementOptionSourceHelper::buildFieldConfig('categories', [
        'source' => 'group:news',
        'labelSource' => 'title',
        'orderBy' => 'title DESC',
    ]);

    expect($config['source'])->toBe('group:news')
        ->and($config)->not->toHaveKey('sources');
})->skip(fn (): bool => !class_exists(\Craft::class) || !\verbb\formie\Formie::$plugin, 'Requires Formie field registry');

it('supports element option sources through the resolver contract', function (): void {
    $resolver = new ElementOptionSourceResolver();
    $source = OptionSource::fromConfig([
        'type' => 'element',
        'provider' => 'entries',
        'params' => [
            'sources' => '*',
        ],
    ]);

    expect($resolver->supports($source))->toBeTrue()
        ->and($resolver->supports(OptionSource::fromConfig([
            'type' => 'predefined',
            'provider' => 'countries',
        ])))->toBeFalse();
})->skip(fn (): bool => !class_exists(\Craft::class) || !\verbb\formie\Formie::$plugin, 'Requires Formie field registry');

it('returns an error when element sources are not configured', function (): void {
    $result = ElementOptionSourceHelper::resolveOptions('categories', []);

    expect($result->error)->not->toBeNull()
        ->and($result->items)->toBe([]);
})->skip(fn (): bool => !class_exists(\Craft::class) || !\verbb\formie\Formie::$plugin, 'Requires Formie field registry');

it('resolves entries through the element option source resolver', function (): void {
    $field = new Dropdown([
        'optionsMode' => OptionsMode::DYNAMIC,
        'optionSource' => [
            'type' => 'element',
            'provider' => 'entries',
            'params' => [
                'sources' => '*',
                'labelSource' => 'title',
                'orderBy' => 'title ASC',
            ],
        ],
    ]);

    $rows = verbb\formie\Formie::$plugin->getOptionSources()->resolveRows($field);

    expect($rows)->toBeArray();
})->skip(fn (): bool => !class_exists(\Craft::class) || !\verbb\formie\Formie::$plugin, 'Requires Formie field registry');
