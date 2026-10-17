<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\fields\Checkboxes;
use verbb\formie\Formie;
use verbb\formie\models\FieldLayout;

it('renders an independent checkbox toggle with no submitted value or required constraint', function (string $position): void {
    $field = new Checkboxes([
        'handle' => 'choices', 'label' => 'Choices', 'required' => true,
        'toggleCheckbox' => $position, 'toggleCheckboxLabel' => 'Select all',
        'options' => [['label' => 'Zero', 'value' => '0'], ['label' => 'One', 'value' => 'one']],
    ]);
    $form = new Form(['title' => 'Checkbox toggle', 'handle' => 'checkboxToggle']);
    $form->setFormLayout(new FieldLayout(['pages' => [['rows' => [['fields' => [$field]]]]]]));
    $html = (string)Formie::$plugin->getRendering()->renderField($form, 'choices');
    $document = new DOMDocument();
    @$document->loadHTML($html);
    $xpath = new DOMXPath($document);
    $toggle = $xpath->query('//input[@data-checkbox-toggle]')->item(0);
    expect($toggle)->not->toBeNull()
        ->and($toggle->hasAttribute('name'))->toBeFalse()
        ->and($toggle->hasAttribute('data-formie-validation-skip'))->toBeTrue()
        ->and($toggle->hasAttribute('required'))->toBeFalse();
    $ids = [];
    foreach ($xpath->query('//input[@type="checkbox"]') as $input) {
        $ids[] = $id = $input->getAttribute('id');
        expect($xpath->query('//label[@for="' . $id . '"]')->length)->toBe(1);
    }
    expect(array_unique($ids))->toHaveCount(3);
})->with(['top', 'bottom']);
