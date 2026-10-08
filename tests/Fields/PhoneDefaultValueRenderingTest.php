<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\fields\Phone;
use verbb\formie\Formie;
use verbb\formie\models\FieldLayout;

it('renders only the phone number in the visible input', function (?string $number, string $country): void {
    $field = new Phone([
        'handle' => 'phone',
        'label' => 'Phone',
        'defaultValue' => $number,
        'countryDefaultValue' => $country,
    ]);
    $form = new Form(['title' => 'Phone defaults', 'handle' => 'phoneDefaults']);
    $form->setFormLayout(new FieldLayout([
        'pages' => [['rows' => [['fields' => [$field]]]]],
    ]));

    $html = (string)Formie::$plugin->getRendering()->renderField($form, 'phone');
    $document = new DOMDocument();
    @$document->loadHTML($html);
    $xpath = new DOMXPath($document);
    $numberInput = $xpath->query('//input[@data-formie-phone-input]')->item(0);
    $countryInput = $xpath->query('//input[@data-formie-phone-country-input]')->item(0);

    expect($numberInput)->not->toBeNull()
        ->and($numberInput->getAttribute('value'))->toBe($number ?? '')
        ->and($countryInput->getAttribute('value'))->toBe($country);
})->with([
    'empty Australian phone' => [null, 'AU'],
    'explicit empty phone' => ['', 'NZ'],
    'populated phone' => ['0412345678', 'AU'],
]);
