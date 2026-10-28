<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Dropdown;
use verbb\formie\Formie;
use verbb\formie\models\FormInstanceConfig;
use verbb\formie\options\predefined\Countries;

it('renders an empty dropdown until a default or answer is supplied', function (string $source, string $expected, bool $searchable): void {
    $options = array_map(static fn(array $country): array => [
        'label' => $country['name'],
        'value' => $country['2-letter'],
        'default' => $source === 'default' && $country['2-letter'] === 'AU',
    ], Countries::getDataOptions());

    $created = formie()->form()->dropdownField('country', [
        'useSearchable' => $searchable,
        'showPlaceholder' => true,
        'required' => true,
        'options' => $options,
    ])->create();
    Formie::$plugin->getFields()->resetFieldRegistryCache();
    $form = Form::find()->id($created->id)->one();
    $field = $form->getFieldByHandle('country');

    if ($source === 'prefill') {
        $form->replaceInstanceConfig(new FormInstanceConfig(initial: [$field->uid => $expected]));
    } elseif ($source === 'submission' || $source === 'empty submission') {
        $saved = formie()->submission($form)->with(['country' => $expected])->save();
        $form->setCurrentSubmission(Submission::find()->id($saved->id)->status(null)->one());
    }

    $html = (string)Formie::$plugin->getRendering()->renderField($form, 'country');
    $document = new DOMDocument();
    @$document->loadHTML('<?xml encoding="UTF-8">' . $html);
    $xpath = new DOMXPath($document);
    $selected = $xpath->query('//select/option[@selected]');

    expect($selected->length)->toBe(1)
        ->and($selected->item(0)->getAttribute('value'))->toBe($expected)
        ->and($xpath->query('//select/option[@value=""]')->length)->toBe(1)
        ->and($field->options)->toBe($options)
        ->and($field->getClientRenderedInput()['options'][0]['value'])->toBe('');
})->with([
    'no default' => ['none', ''],
    'configured default' => ['default', 'AU'],
    'prefilled answer' => ['prefill', 'NZ'],
    'saved answer' => ['submission', 'AU'],
    'cleared answer' => ['empty submission', ''],
])->with([false, true]);

it('preserves authored empty options and ordinary dropdown behavior', function (array $settings, array $expectedValues, string $expectedLabel): void {
    $field = new Dropdown(array_merge([
        'useSearchable' => true,
        'showPlaceholder' => true,
        'options' => [['label' => 'Zero', 'value' => '0'], ['label' => 'One', 'value' => '1']],
    ], $settings));

    $options = $field->getFieldOptions();

    expect(array_column($options, 'value'))->toBe($expectedValues)
        ->and($options[0]['label'])->toBe($expectedLabel);
})->with([
    'searchable single' => [[], ['', '0', '1'], 'Select an option'],
    'ordinary single' => [['useSearchable' => false], ['', '0', '1'], 'Select an option'],
    'disabled placeholder' => [['showPlaceholder' => false], ['0', '1'], 'Zero'],
    'blank placeholder label' => [['placeholder' => ''], ['', '0', '1'], ''],
    'searchable multi' => [['multi' => true], ['0', '1'], 'Zero'],
    'custom placeholder' => [['placeholder' => 'Choose a number'], ['', '0', '1'], 'Choose a number'],
    'authored empty option' => [['options' => [['label' => 'Pick one', 'value' => ''], ['label' => 'Zero', 'value' => '0']]], ['', '', '0'], 'Select an option'],
    'optgroup' => [['options' => [['optgroup' => 'Numbers'], ['label' => 'Zero', 'value' => '0']]], ['', '0'], 'Select an option'],
]);
