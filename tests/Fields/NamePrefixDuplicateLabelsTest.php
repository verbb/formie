<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\fields\Name;
use verbb\formie\fields\subfields\NamePrefix;

it('saves a multi-part Name whose translated Prefix options share a label', function(): void {
    $language = Craft::$app->language;

    // German translates both "Mrs." and "Ms." to "Frau", so the default Prefix options repeat a label.
    Craft::$app->language = 'de';

    try {
        // Turn on the Prefix sub-field, as the form builder's toggle does.
        $rows = (new Name(['useMultipleFields' => true]))->getSubFields();
        $rows[0]['fields'][0]['enabled'] = true;

        expect($rows[0]['fields'][0]['type'])->toBe(NamePrefix::class);

        $form = formie()->form()->nameField('person', ['label' => 'Name', 'useMultipleFields' => true, 'rows' => $rows])->create();

        // Save again, as the form builder does on every later save.
        expect(Craft::$app->getElements()->saveElement($form))->toBeTrue(json_encode($form->getErrors()));
    } finally {
        Craft::$app->language = $language;
    }

    Formie::$plugin->getForms()->invalidateFormCaches();
    $saved = Formie::$plugin->getForms()->getFormById((int)$form->id);
    $prefix = $saved->getFieldByHandle('person')->getFieldByHandle('prefix');

    expect($prefix)->toBeInstanceOf(NamePrefix::class);

    $labels = array_column($prefix->options, 'label');

    expect(array_filter($labels, 'is_string'))->toBe($labels)
        ->and(array_count_values($labels)['Frau'] ?? 0)->toBe(2);
});

it('keeps repeated Prefix labels unchanged across repeated validation', function(): void {
    $options = [
        ['label' => 'Frau', 'value' => 'mrs'],
        ['label' => 'Frau', 'value' => 'ms'],
    ];
    $field = new NamePrefix(['handle' => 'prefix', 'label' => 'Prefix', 'options' => $options]);

    $field->validateOptions();
    $field->validateOptions();

    expect($field->hasErrors('options'))->toBeFalse()
        ->and(array_map(static fn(array $option): array => array_intersect_key($option, ['label' => true, 'value' => true]), $field->options))->toBe($options);
});
